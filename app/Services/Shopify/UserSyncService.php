<?php

namespace App\Services\Shopify;

use App\Models\StoreUser;
use App\Models\Shopify;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UserSyncService
{
    protected $integration;
    protected $apiVersion = '2025-04';
    protected $pageSize = 250; // Shopify API max limit

    public function sync(Shopify $integration)
    {
        $this->integration = $integration;
        
        try {
            $users = $this->fetchAllUsers();
            $this->processUsers($users);
            
            return [
                'success' => true,
                'count' => count($users),
                'last_sync' => now()->toDateTimeString()
            ];
            
        } catch (\Exception $e) {
            Log::error("Shopify User Sync Error: {$e->getMessage()}");
            throw $e;
        }
    }

    protected function fetchAllUsers()
    {
        $users = [];
        $pageInfo = null;
        $hasNextPage = true;
        $attempts = 0;
        $maxAttempts = 100;

        while ($hasNextPage && $attempts < $maxAttempts) {
            $attempts++;
            $response = $this->makeUsersRequest($pageInfo);
            
            if ($response->failed()) {
                throw new \Exception("Shopify Users API Error: {$response->body()}");
            }

            $data = $response->json();
            $users = array_merge($users, $data['users']);

            $linkHeader = $response->header('Link');
            $hasNextPage = $this->hasNextPage($linkHeader);
            
            if ($hasNextPage) {
                $pageInfo = $this->getNextPageInfo($linkHeader);
                sleep(1); // Rate limit handling
            }
        }

        return $users;
    }

    protected function makeUsersRequest($pageInfo = null)
    {
        $baseUrl = "https://{$this->integration->shopify_domain}.myshopify.com";
        $endpoint = "/admin/api/{$this->apiVersion}/users.json";
        $accessToken = $this->integration->admin_token;

        return Http::withHeaders([
            'X-Shopify-Access-Token' => $accessToken,
            'Accept' => 'application/json',
        ])->get($baseUrl . $endpoint);
    }

    protected function processUsers(array $users)
    {
        foreach ($users as $shopifyUser) {
            try {
                $this->processUser($shopifyUser);
            } catch (\Exception $e) {
                Log::error("Failed to process user {$shopifyUser['id']}: " . $e->getMessage());
                continue;
            }
        }
    }

    protected function processUser(array $shopifyUser)
    {
        // Prepara os dados de permissões
        $permissions = $this->extractPermissions($shopifyUser);

        StoreUser::updateOrCreate(
            [
                'admin_graphql_api_id' => $shopifyUser['admin_graphql_api_id'],
                'first_name' => $shopifyUser['first_name'],
                'last_name' => $shopifyUser['last_name'],
                'email' => $shopifyUser['email'],
                'url' => $shopifyUser['url'] ?? null,
                'im' => $shopifyUser['im'] ?? null,
                'screen_name' => $shopifyUser['screen_name'] ?? null,
                'phone' => $shopifyUser['phone'] ?? null,
                'bio' => $shopifyUser['bio'] ?? null,
                'account_owner' => $shopifyUser['account_owner'],
                'receive_announcements' => $shopifyUser['receive_announcements'],
                'locale' => $shopifyUser['locale'] ?? null,
                'user_type' => $shopifyUser['user_type'] ?? null,
                'tfa_enabled' => $shopifyUser['tfa_enabled'] ?? false,
                'permissions' => $permissions,
                'created_at' => $shopifyUser['created_at'],
                'updated_at' => $shopifyUser['updated_at'],
                'store_id' => $this->integration->store_id,
            ]
        );
    }

    protected function extractPermissions(array $shopifyUser)
    {
        // Shopify retorna permissões em diferentes formatos dependendo do user_type
        if ($shopifyUser['account_owner']) {
            return ['full_access']; // Donos da conta têm todas as permissões
        }

        if (isset($shopifyUser['permissions'])) {
            if (is_array($shopifyUser['permissions'])) {
                return $shopifyUser['permissions'];
            }
            
            // Caso as permissões venham como string delimitada
            if (is_string($shopifyUser['permissions'])) {
                return explode(',', $shopifyUser['permissions']);
            }
        }

        return []; // Sem permissões definidas
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