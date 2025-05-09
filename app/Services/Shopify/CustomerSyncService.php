<?php

namespace App\Services\Shopify;

use App\Models\Shopify;
use App\Models\Customer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CustomerSyncService
{
    protected $integration;
    protected $apiVersion = '2025-04';
    protected $pageSize = 250; // Shopify API max limit

    public function sync(Shopify $integration)
    {
        $this->integration = $integration;
        
        try {
            $customers = $this->fetchAllCustomers();

            $this->processCustomers($customers);
            
            return [
                'success' => true,
                'count' => count($customers),
                'last_sync' => now()->toDateTimeString()
            ];
            
        } catch (\Exception $e) {
            Log::error("Shopify Customer Sync Error: {$e->getMessage()}");
            throw $e;
        }
    }

    protected function fetchAllCustomers()
    {
        $customers = [];
        $pageInfo = null;
        $hasNextPage = true;
        $attempts = 0;
        $maxAttempts = 100;

        while ($hasNextPage && $attempts < $maxAttempts) {
            $attempts++;
            $response = $this->makeCustomersRequest($pageInfo);
            
            if ($response->failed()) {
                throw new \Exception("Shopify Customers API Error: {$response->body()}");
            }

            $data = $response->json();
            $customers = array_merge($customers, $data['customers']);

            $linkHeader = $response->header('Link');
            $hasNextPage = $this->hasNextPage($linkHeader);
            
            if ($hasNextPage) {
                $pageInfo = $this->getNextPageInfo($linkHeader);
                sleep(1); // Rate limit handling
            }
        }

        return $customers;
    }

    protected function makeCustomersRequest($pageInfo = null)
    {
        $baseUrl = "https://{$this->integration->shopify_domain}.myshopify.com";
        $endpoint = "/admin/api/{$this->apiVersion}/customers.json";
        $accessToken = $this->integration->admin_token;

        $queryParams = ['limit' => $this->pageSize];
        if ($pageInfo) {
            $queryParams['page_info'] = $pageInfo;
        } else {
            $queryParams['fields'] = implode(',', [
                'id',
                'email',
                'first_name',
                'last_name',
                'phone',
                'verified_email',
                'tax_exempt',
                'tags',
                'created_at',
                'updated_at',
                'default_address',
                'addresses',
                'accepts_marketing',
                'currency',
                'last_order_id',
                'last_order_name',
                'orders_count',
                'total_spent'
            ]);
        }

        return Http::withHeaders([
            'X-Shopify-Access-Token' => $accessToken,
            'Accept' => 'application/json',
        ])->get($baseUrl . $endpoint, $queryParams);
    }

    protected function processCustomers(array $customers)
    {
        foreach ($customers as $shopifyCustomer) {
            Customer::updateOrCreate(
                  ['id' => $shopifyCustomer['id']],
                  [
                      'store_id' => $this->integration->store_id,
                      'email' => $shopifyCustomer['email'],
                      'first_name' => $shopifyCustomer['first_name'],
                      'state' => 'disabled',
                      'last_name' => $shopifyCustomer['last_name'],
                      'phone' => $shopifyCustomer['phone'] ?? null,
                      'verified_email' => $shopifyCustomer['verified_email'],
                      'tax_exempt' => $shopifyCustomer['tax_exempt'],
                      'tags' => $shopifyCustomer['tags'],
                      'currency' => $shopifyCustomer['currency'],
                      'last_order_id' => $shopifyCustomer['last_order_id'] ?? null,
                      'last_order_name' => $shopifyCustomer['last_order_name'] ?? null,
                      'orders_count' => $shopifyCustomer['orders_count'],
                      'total_spent' => $shopifyCustomer['total_spent'],
                      'default_address' => json_encode($shopifyCustomer['default_address'] ?? null),
                      'addresses' => json_encode($shopifyCustomer['addresses'] ?? []),
                      'shopify_data' => json_encode($shopifyCustomer)
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