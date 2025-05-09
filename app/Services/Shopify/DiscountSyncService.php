<?php

namespace App\Services\Shopify;

use App\Models\Shopify;
use App\Models\DiscountCode;
use App\Models\PriceRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DiscountSyncService
{
    protected $integration;
    protected $apiVersion = '2025-04';
    protected $pageSize = 250; // Max allowed by Shopify API

    public function sync(Shopify $integration)
    {
        $this->integration = $integration;
        
        try {
            // Sync price rules first (they contain the discount codes)
            $priceRules = $this->fetchAllPriceRules();
            $this->processPriceRules($priceRules);
            
            return [
                'success' => true,
                'price_rules_count' => count($priceRules),
                'last_sync' => now()->toDateTimeString()
            ];
            
        } catch (\Exception $e) {
            Log::error("Shopify Discount Sync Error: {$e->getMessage()}");
            throw $e;
        }
    }

    protected function fetchAllPriceRules()
    {
        $priceRules = [];
        $pageInfo = null;
        $hasNextPage = true;
        $attempts = 0;
        $maxAttempts = 100;

        while ($hasNextPage && $attempts < $maxAttempts) {
            $attempts++;
            $response = $this->makePriceRulesRequest($pageInfo);
            
            if ($response->failed()) {
                throw new \Exception("Shopify Price Rules API Error: {$response->body()}");
            }

            $data = $response->json();
            $priceRules = array_merge($priceRules, $data['price_rules']);

            $linkHeader = $response->header('Link');
            $hasNextPage = $this->hasNextPage($linkHeader);
            
            if ($hasNextPage) {
                $pageInfo = $this->getNextPageInfo($linkHeader);
                sleep(1); // Rate limiting
            }
        }

        return $priceRules;
    }

    protected function makePriceRulesRequest($pageInfo = null)
    {
        $baseUrl = "https://{$this->integration->shopify_domain}.myshopify.com";
        $endpoint = "/admin/api/{$this->apiVersion}/price_rules.json";
        $accessToken = $this->integration->admin_token;

        $queryParams = ['limit' => $this->pageSize];
        if ($pageInfo) {
            $queryParams['page_info'] = $pageInfo;
        } else {
            $queryParams['fields'] = 'id,title,value_type,value,once_per_customer,customer_selection,allocation_method,starts_at,ends_at,created_at,updated_at,usage_limit,status,target_selection,target_type';
        }

        return Http::withHeaders([
            'X-Shopify-Access-Token' => $accessToken,
            'Accept' => 'application/json',
        ])->get($baseUrl . $endpoint, $queryParams);
    }

    protected function processPriceRules(array $priceRules)
    {
        foreach ($priceRules as $priceRule) {
            // First save the price rule
            $rule = PriceRule::updateOrCreate(
                ['id' => $priceRule['id']],
                [
                    'title' => $priceRule['title'],
                    'value_type' => $priceRule['value_type'],
                    'value' => $priceRule['value'],
                    'once_per_customer' => $priceRule['once_per_customer'],
                    'customer_selection' => $priceRule['customer_selection'],
                    'allocation_method' => $priceRule['allocation_method'],
                    'starts_at' => $priceRule['starts_at'],
                    'ends_at' => $priceRule['ends_at'],
                    'usage_limit' => $priceRule['usage_limit'] ?? null,
                    'target_selection' => $priceRule['target_selection'],
                    'target_type' => $priceRule['target_type'],
                    'shopify_data' => json_encode($priceRule)
                ]
            );

            // Then fetch and save all discount codes for this price rule
            $this->fetchAndProcessDiscountCodes($priceRule['id']);
        }
    }

    protected function fetchAndProcessDiscountCodes($priceRuleId)
    {
        $response = $this->makeDiscountCodesRequest($priceRuleId);
        
        if ($response->failed()) {
            Log::error("Failed to fetch discount codes for price rule {$priceRuleId}");
            return;
        }

        $data = $response->json();
        $this->processDiscountCodes($priceRuleId, $data['discount_codes']);
    }

    protected function makeDiscountCodesRequest($priceRuleId)
    {
        $baseUrl = "https://{$this->integration->shopify_domain}.myshopify.com";
        $endpoint = "/admin/api/{$this->apiVersion}/price_rules/{$priceRuleId}/discount_codes.json";
        $accessToken = $this->integration->admin_token;

        return Http::withHeaders([
            'X-Shopify-Access-Token' => $accessToken,
            'Accept' => 'application/json',
        ])->get($baseUrl . $endpoint);
    }

    protected function processDiscountCodes($priceRuleId, array $discountCodes)
    {
        foreach ($discountCodes as $discountCode) {
            try {
                DiscountCode::updateOrCreate(
                    [
                        'code' => $discountCode['code'], // Use o código como chave de identificação
                        'price_rule_id' => $priceRuleId  // Junto com o price_rule_id
                    ],
                    [
                        'id' => $discountCode['id'],
                        'usage_count' => $discountCode['usage_count'],
                        'created_at' => $discountCode['created_at'],
                        'updated_at' => $discountCode['updated_at'],
                        'shopify_data' => json_encode($discountCode) // Adicione para registrar dados completos
                    ]
                );
            } catch (\Exception $e) {
                Log::warning("Duplicate discount code detected: {$discountCode['code']}. Updating existing record.");
                
                // Fallback: Atualiza o registro existente
                $existing = DiscountCode::where('code', $discountCode['code'])
                                    ->where('price_rule_id', $priceRuleId)
                                    ->first();
                
                if ($existing) {
                    $existing->update([
                        'usage_count' => $discountCode['usage_count'],
                        'updated_at' => $discountCode['updated_at'],
                        'shopify_data' => json_encode($discountCode)
                    ]);
                }
            }
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