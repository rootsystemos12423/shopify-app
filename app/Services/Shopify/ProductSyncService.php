<?php

namespace App\Services\Shopify;

use App\Models\Shopify;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductOption;
use App\Models\ProductImage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Collection;

class ProductSyncService
{
    protected $integration;
    protected $apiVersion = '2025-04';
    protected $pageSize = 250; // Max allowed by Shopify API

    public function sync(Shopify $integration)
    {
        $this->integration = $integration;
        
        try {
            $products = $this->fetchAllProducts();
            $this->processProducts($products);
            
            // Sincronizar coleções
            $collections = $this->fetchAllCollections();
            $this->processCollections($collections);
            
            return [
                'success' => true,
                'count' => count($products),
                'collections_count' => count($collections),
                'last_sync' => now()->toDateTimeString()
            ];
            
        } catch (\Exception $e) {
            Log::error("Shopify Sync Error: {$e->getMessage()}");
            throw $e;
        }
    }

    protected function fetchAllProducts()
    {
        $products = [];
        $pageInfo = null;
        $hasNextPage = true;
        $attempts = 0;
        $maxAttempts = 100; // Safety limit to prevent infinite loops

        while ($hasNextPage && $attempts < $maxAttempts) {
            $attempts++;
            $response = $this->makeShopifyRequest($pageInfo);
            
            if ($response->failed()) {
                throw new \Exception("Shopify API Error: {$response->body()}");
            }

            $data = $response->json();
            $products = array_merge($products, $data['products']);

            // Get pagination info from headers
            $linkHeader = $response->header('Link');
            $hasNextPage = $this->hasNextPage($linkHeader);
            
            if ($hasNextPage) {
                $pageInfo = $this->getNextPageInfo($linkHeader);
                sleep(1); // Rate limiting protection
            }
        }

        return $products;
    }

    protected function hasNextPage($linkHeader)
    {
        return str_contains($linkHeader, 'rel="next"');
    }

    protected function getNextPageInfo($linkHeader)
    {
        if (preg_match('/<([^>]+)>; rel="next"/', $linkHeader, $matches)) {
            $url = $matches[1];
            parse_str(parse_url($url, PHP_URL_QUERY), $queryParams);
            return $queryParams['page_info'] ?? null;
        }
        return null;
    }

    protected function makeShopifyRequest($pageInfo = null)
    {
        $baseUrl = "https://{$this->integration->shopify_domain}.myshopify.com";
        $endpoint = "/admin/api/{$this->apiVersion}/products.json";
        $accessToken = $this->integration->admin_token;

        $queryParams = ['limit' => $this->pageSize];
        if ($pageInfo) {
            $queryParams['page_info'] = $pageInfo;
        } else {
            $queryParams['fields'] = 'id,title,vendor,product_type,handle,status,tags,published_at,images,variants,options,body_html';
        }

        return Http::withHeaders([
            'X-Shopify-Access-Token' => $accessToken,
            'Accept' => 'application/json',
        ])->get($baseUrl . $endpoint, $queryParams);
    }

    protected function processProducts(array $products)
    {
        foreach ($products as $shopifyProduct) {
            $product = $this->createOrUpdateProduct($shopifyProduct);
            $this->processVariants($product, $shopifyProduct['variants']);
            $this->processOptions($product, $shopifyProduct['options']);
            $this->processImages($product, $shopifyProduct['images']);
        }
    }

    protected function createOrUpdateProduct(array $shopifyProduct)
    {
        return Product::updateOrCreate(
            ['id' => $shopifyProduct['id']],
            [
                'store_id' => $this->integration->store_id,
                'title' => $shopifyProduct['title'],
                'body_html' => $shopifyProduct['body_html'],
                'vendor' => $shopifyProduct['vendor'],
                'product_type' => $shopifyProduct['product_type'],
                'handle' => $shopifyProduct['handle'],
                'status' => $shopifyProduct['status'],
                'tags' => $shopifyProduct['tags'],
                'published_at' => $shopifyProduct['published_at'],
                'shopify_data' => json_encode($shopifyProduct)
            ]
        );
    }

    protected function fetchAllCollections()
    {
        $collections = [];
        $pageInfo = null;
        $hasNextPage = true;
        $attempts = 0;
        $maxAttempts = 100;

        while ($hasNextPage && $attempts < $maxAttempts) {
            $attempts++;
            $response = $this->makeCollectionsRequest($pageInfo);
            
            if ($response->failed()) {
                throw new \Exception("Shopify Smart Collections API Error: {$response->body()}");
            }

            $data = $response->json();
            $collections = array_merge($collections, $data['smart_collections']); // Changed from 'custom_collections'

            $linkHeader = $response->header('Link');
            $hasNextPage = $this->hasNextPage($linkHeader);
            
            if ($hasNextPage) {
                $pageInfo = $this->getNextPageInfo($linkHeader);
                sleep(1); // Rate limit handling
            }
        }

        return $collections;
    }

    protected function makeCollectionsRequest($pageInfo = null)
    {
        $baseUrl = "https://{$this->integration->shopify_domain}.myshopify.com";
        $endpoint = "/admin/api/{$this->apiVersion}/smart_collections.json"; // Changed endpoint
        $accessToken = $this->integration->admin_token;

        $queryParams = ['limit' => $this->pageSize];
        if ($pageInfo) {
            $queryParams['page_info'] = $pageInfo;
        } else {
            // Fields specific to Smart Collections
            $queryParams['fields'] = 'id,title,handle,body_html,published_at,sort_order,template_suffix,disjunctive,rules,published_scope,products_count,image';
        }

        return Http::withHeaders([
            'X-Shopify-Access-Token' => $accessToken,
            'Accept' => 'application/json',
        ])->get($baseUrl . $endpoint, $queryParams);
    }

    protected function processCollections(array $collections)
    {
        foreach ($collections as $collection) {
            Collection::updateOrCreate(
                ['id' => $collection['id']],
                [
                    'store_id' => $this->integration->store_id,
                    'title' => $collection['title'],
                    'handle' => $collection['handle'],
                    'body_html' => $collection['body_html'] ?? null,
                    'published_at' => $collection['published_at'],
                    'sort_order' => $collection['sort_order'], // New field
                    'template_suffix' => $collection['template_suffix'] ?? null, // New field
                    'disjunctive' => $collection['disjunctive'], // New field
                    'rules' => $collection['rules'], // New field (automatically cast to JSON)
                    'published_scope' => $collection['published_scope'], // New field
                    'shopify_data' => json_encode($collection) // Raw data backup
                ]
            );
        }
    }

    protected function processVariants(Product $product, array $variants)
{
    foreach ($variants as $variant) {
        try {
            ProductVariant::updateOrCreate(
                ['id' => $variant['id']],
                [
                    'product_id' => $variant['product_id'], // Garantindo que usamos o ID do produto criado
                    'title' => $variant['title'] ?? 'Default Title',
                    'price' => $variant['price'] ?? 0.00,
                    'compare_at_price' => $variant['compare_at_price'] ?? null,
                    'sku' => $variant['sku'] ?? '',
                    'barcode' => $variant['barcode'] ?? '',
                    'position' => $variant['position'] ?? 1,
                    'inventory_policy' => $variant['inventory_policy'] ?? 'deny',
                    'inventory_management' => $variant['inventory_management'] ?? 'shopify',
                    'inventory_quantity' => $variant['inventory_quantity'] ?? 0,
                    'requires_shipping' => $variant['requires_shipping'] ?? true,
                    'taxable' => $variant['taxable'] ?? true,
                    'grams' => $variant['grams'] ?? 0,
                    'weight' => $variant['weight'] ?? 0,
                    'weight_unit' => $variant['weight_unit'] ?? 'kg',
                    'option1' => $variant['option1'] ?? null,
                    'option2' => $variant['option2'] ?? null,
                    'option3' => $variant['option3'] ?? null,
                    'shopify_data' => json_encode($variant)
                ]
            );
        } catch (\Exception $e) {
            Log::error("Failed to process variant {$variant['id']} for product {$product->id}: " . $e->getMessage());
            continue;
        }
    }
}

    protected function processOptions(Product $product, array $options)
    {
        foreach ($options as $option) {
            ProductOption::updateOrCreate(
                ['id' => $option['id']],
                [
                    'product_id' => $option['product_id'],
                    'name' => $option['name'],
                    'position' => $option['position'],
                    'values' => json_encode($option['values'])
                ]
            );
        }
    }

    protected function processImages(Product $product, array $images)
    {
        foreach ($images as $image) {
            ProductImage::updateOrCreate(
                ['id' => $image['id']],
                [
                    'product_id' => $image['product_id'],
                    'src' => $image['src'],
                    'alt' => $image['alt'],
                    'width' => $image['width'],
                    'height' => $image['height'],
                    'position' => $image['position'],
                    'variant_ids' => json_encode($image['variant_ids'])
                ]
            );
        }
    }
}