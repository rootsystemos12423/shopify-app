<?php

namespace App\Services\Shopify;

use App\Models\Shopify;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Customer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OrderSyncService
{
    protected $integration;
    protected $apiVersion = '2025-04';
    protected $pageSize = 250; // Shopify API max limit

    public function sync(Shopify $integration)
    {
        $this->integration = $integration;
        
        try {
            $orders = $this->fetchAllOrders();
            $this->processOrders($orders);
            
            return [
                'success' => true,
                'count' => count($orders),
                'last_sync' => now()->toDateTimeString()
            ];
            
        } catch (\Exception $e) {
            Log::error("Shopify Order Sync Error: {$e->getMessage()}");
            throw $e;
        }
    }

    protected function fetchAllOrders()
    {
        $orders = [];
        $pageInfo = null;
        $hasNextPage = true;
        $attempts = 0;
        $maxAttempts = 100;

        while ($hasNextPage && $attempts < $maxAttempts) {
            $attempts++;
            $response = $this->makeOrdersRequest($pageInfo);
            
            if ($response->failed()) {
                throw new \Exception("Shopify Orders API Error: {$response->body()}");
            }

            $data = $response->json();
            $orders = array_merge($orders, $data['orders']);

            $linkHeader = $response->header('Link');
            $hasNextPage = $this->hasNextPage($linkHeader);
            
            if ($hasNextPage) {
                $pageInfo = $this->getNextPageInfo($linkHeader);
                sleep(1); // Rate limit handling
            }
        }

        return $orders;
    }

    protected function makeOrdersRequest($pageInfo = null)
    {
        $baseUrl = "https://{$this->integration->shopify_domain}.myshopify.com";
        $endpoint = "/admin/api/{$this->apiVersion}/orders.json";
        $accessToken = $this->integration->admin_token;

        $queryParams = [
            'limit' => $this->pageSize,
            'status' => 'any' // Get orders of any status
        ];

        if ($pageInfo) {
            $queryParams['page_info'] = $pageInfo;
        } else {
            // Fields to request (optimized to match your model)
            $queryParams['fields'] = implode(',', [
                'id',
                'admin_graphql_api_id',
                'order_number',
                'email',
                'currency',
                'current_subtotal_price',
                'current_total_discounts',
                'current_total_price',
                'current_total_tax',
                'financial_status',
                'fulfillment_status',
                'name',
                'phone',
                'presentment_currency',
                'processed_at',
                'source_name',
                'subtotal_price',
                'total_discounts',
                'total_line_items_price',
                'total_outstanding',
                'total_price',
                'total_tax',
                'total_tip_received',
                'total_weight',
                'token',
                'billing_address',
                'shipping_address',
                'client_details',
                'discount_codes',
                'note_attributes',
                'tags',
                'payment_gateway_names',
                'customer',
                'line_items'
            ]);
        }

        return Http::withHeaders([
            'X-Shopify-Access-Token' => $accessToken,
            'Accept' => 'application/json',
        ])->get($baseUrl . $endpoint, $queryParams);
    }

    protected function processOrders(array $orders)
    {
        foreach ($orders as $shopifyOrder) {
            try {
                // Create/update the order
                $order = Order::updateOrCreate(
                    ['order_number' => $shopifyOrder['order_number']],
                    [
                        'admin_graphql_api_id' => $shopifyOrder['admin_graphql_api_id'],
                        'store_id' => $this->integration->store_id,
                        'email' => $shopifyOrder['email'],
                        'currency' => $shopifyOrder['currency'],
                        'current_subtotal_price' => $shopifyOrder['current_subtotal_price'],
                        'current_total_discounts' => $shopifyOrder['current_total_discounts'],
                        'current_total_price' => $shopifyOrder['current_total_price'],
                        'current_total_tax' => $shopifyOrder['current_total_tax'],
                        'financial_status' => $shopifyOrder['financial_status'],
                        'fulfillment_status' => $shopifyOrder['fulfillment_status'],
                        'name' => $shopifyOrder['name'],
                        'phone' => $shopifyOrder['phone'] ?? null,
                        'presentment_currency' => $shopifyOrder['presentment_currency'],
                        'processed_at' => $shopifyOrder['processed_at'],
                        'source_name' => $shopifyOrder['source_name'],
                        'subtotal_price' => $shopifyOrder['subtotal_price'],
                        'total_discounts' => $shopifyOrder['total_discounts'],
                        'total_line_items_price' => $shopifyOrder['total_line_items_price'],
                        'total_outstanding' => $shopifyOrder['total_outstanding'],
                        'total_price' => $shopifyOrder['total_price'],
                        'total_tax' => $shopifyOrder['total_tax'],
                        'total_tip_received' => $shopifyOrder['total_tip_received'] ?? 0,
                        'total_weight' => $shopifyOrder['total_weight'],
                        'token' => $shopifyOrder['token'],
                        'billing_address' => json_encode($shopifyOrder['billing_address'] ?? null),
                        'shipping_address' => json_encode($shopifyOrder['shipping_address'] ?? null),
                        'client_details' => json_encode($shopifyOrder['client_details'] ?? null),
                        'discount_codes' => json_encode($shopifyOrder['discount_codes'] ?? []),
                        'note_attributes' => json_encode($shopifyOrder['note_attributes'] ?? []),
                        'tags' => $shopifyOrder['tags'],
                        'payment_gateway_names' => json_encode($shopifyOrder['payment_gateway_names'] ?? []),
                    ]
                );

                // Process order items
                if (!empty($shopifyOrder['line_items'])) {
                    $this->processOrderItems($order, $shopifyOrder['line_items']);
                }

            } catch (\Exception $e) {
                Log::error("Failed to process order {$shopifyOrder['id']}: " . $e->getMessage());
                continue;
            }
        }
    }

    protected function processOrderItems(Order $order, array $lineItems)
    {
        foreach ($lineItems as $item) {
            OrderItem::updateOrCreate(
                [
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'] ?? null,
                    'variant_id' => $item['variant_id'] ?? null,
                    'title' => $item['title'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'grams' => $item['grams'],
                    'sku' => $item['sku'] ?? null,
                    'variant_title' => $item['variant_title'] ?? null,
                    'fulfillment_status' => $item['fulfillment_status'] ?? null,
                    'requires_shipping' => $item['requires_shipping'],
                    'taxable' => $item['taxable'],
                    'gift_card' => $item['gift_card'] ?? false,
                    'name' => $item['name'],
                    'vendor' => $item['vendor'] ?? null,
                    'properties' => json_encode($item['properties'] ?? []),
                    'tax_lines' => json_encode($item['tax_lines'] ?? []),
                    'discount_allocations' => json_encode($item['discount_allocations'] ?? []),
                    'price_set' => json_encode($item['price_set'] ?? []),
                    'total_discount_set' => json_encode($item['total_discount_set'] ?? []),
                ]
            );
        }
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
}