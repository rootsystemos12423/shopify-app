<?php

namespace App\Services\Shopify;

use App\Models\Shopify;
use App\Models\Theme;
use App\Models\ThemeVersion;
use App\Models\ThemeAsset;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ThemeSyncService
{
    protected $integration;
    protected $apiVersion = '2025-04';
    protected $pageSize = 250; // Shopify API max limit para assets

    /**
     * Sincroniza todos os temas da loja
     */
    public function sync(Shopify $integration): array
    {
        $this->integration = $integration;
    
        try {
            $themes = $this->fetchAllThemes();
            $syncedThemes = [];
            
            // Primeiro, sincroniza os dados básicos de todos os temas
            foreach ($themes as $shopifyTheme) {
                $theme = $this->syncThemeData($shopifyTheme);
                $syncedThemes[] = $theme;
            }
            
            // Depois, sincroniza os assets de todos os temas (ou só do tema principal, dependendo da necessidade)
            // Podemos sincronizar todos os temas ou apenas os temas específicos
            $themesWithAssets = 0;
            foreach ($themes as $shopifyTheme) {
                // Opção 1: Sincroniza apenas o tema principal (comportamento original)
                // if ($shopifyTheme['role'] === 'main') {
                
                // Opção 2: Sincroniza todos os temas
                // Descomente a linha abaixo e comente a linha acima se quiser sincronizar todos
                
                // Opção 3: Sincroniza temas com roles específicos (exemplo: 'main' e 'published')
                if (in_array($shopifyTheme['role'], ['main', 'published', 'unpublished'])) {
                    $theme = Theme::where('shopify_theme_id', $shopifyTheme['id'])->first();
                    if ($theme) {
                        $this->syncThemeAssets($theme, $shopifyTheme['id']);
                        $themesWithAssets++;
                    }
                }
            }
    
            return [
                'success' => true,
                'count' => count($syncedThemes),
                'themes_with_assets' => $themesWithAssets,
                'last_sync' => now()->toDateTimeString()
            ];
    
        } catch (\Exception $e) {
            Log::error("Shopify Theme Sync Error: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Busca todos os temas com paginação
     */
    protected function fetchAllThemes(): array
    {
        $themes = [];
        $response = $this->makeThemesRequest();

        if ($response->failed()) {
            throw new \Exception("Shopify Themes API Error: {$response->body()}");
        }

        return $response->json()['themes'] ?? [];
    }

    /**
     * Faz a requisição para a API de temas
     */
    protected function makeThemesRequest()
    {
        $baseUrl = "https://{$this->integration->shopify_domain}.myshopify.com/admin/api/{$this->apiVersion}/";
        $endpoint = "themes.json";

        return Http::withHeaders([
            'X-Shopify-Access-Token' => $this->integration->admin_token,
            'Accept' => 'application/json',
        ])->get($baseUrl . $endpoint);
    }

    /**
     * Sincroniza os dados básicos de um tema
     */
    protected function syncThemeData(array $shopifyTheme): Theme
    {
        return Theme::updateOrCreate(
            ['shopify_theme_id' => $shopifyTheme['id']],
            [
                'store_id' => $this->integration->store_id,
                'name' => $shopifyTheme['name'],
                'role' => $shopifyTheme['role'],
                'active' => $shopifyTheme['role'] === 'main',
                'shopify_data' => $shopifyTheme,
            ]
        );
    }

    /**
     * Sincroniza todos os assets de um tema com paginação
     */
    protected function syncThemeAssets(Theme $theme, string $themeId): void
    {
        $assets = $this->fetchAllThemeAssets($themeId);
        $versionName = 'v' . now()->format('YmdHis');
        $manifest = [];

        $version = $theme->createNewVersion(
            $versionName, 
            $theme->version // Ou outro valor de versão válido
        );

        foreach ($assets as $asset) {
            try {
                $assetData = $this->processAsset($theme, $version, $asset);
                $manifest[$asset['key']] = $assetData['checksum'];
            } catch (\Exception $e) {
                Log::error("Failed to sync asset {$asset['key']}: " . $e->getMessage());
                continue;
            }
        }

        $version->update(['assets_manifest' => $manifest]);
        $theme->activateVersion($versionName);
    }

    /**
     * Busca todos os assets de um tema com paginação
     */
    protected function fetchAllThemeAssets(string $themeId): array
    {
        $assets = [];
        $pageInfo = null;
        $hasNextPage = true;
        $attempts = 0;
        $maxAttempts = 100;

        while ($hasNextPage && $attempts < $maxAttempts) {
            $attempts++;
            $response = $this->makeAssetsRequest($themeId, $pageInfo);

            if ($response->failed()) {
                throw new \Exception("Shopify Assets API Error: {$response->body()}");
            }

            $data = $response->json();
            $assets = array_merge($assets, $data['assets'] ?? []);

            $linkHeader = $response->header('Link');
            $hasNextPage = $this->hasNextPage($linkHeader);

            if ($hasNextPage) {
                $pageInfo = $this->getNextPageInfo($linkHeader);
                sleep(1); // Rate limit handling
            }
        }

        return $assets;
    }

    /**
     * Faz a requisição para a API de assets
     */
    protected function makeAssetsRequest(string $themeId, ?string $pageInfo = null)
    {
        $baseUrl = "https://{$this->integration->shopify_domain}.myshopify.com/admin/api/{$this->apiVersion}/";
        $endpoint = "themes/{$themeId}/assets.json";
        $query = ['fields' => 'key,public_url,content_type,size,created_at,updated_at'];

        if ($pageInfo) {
            $query['page_info'] = $pageInfo;
        }

        return Http::withHeaders([
            'X-Shopify-Access-Token' => $this->integration->admin_token,
            'Accept' => 'application/json',
        ])->get($baseUrl . $endpoint, $query);
    }

    /**
     * Processa e armazena um asset individual
     */
    protected function processAsset(Theme $theme, ThemeVersion $version, array $asset): array
    {
        $assetDetail = $this->fetchAssetContent($theme->shopify_theme_id, $asset['key']);
        $content = $assetDetail['asset']['value'] ?? null;
        
        // Decodifica se for base64 (a Shopify retorna alguns assets codificados)
        $content = $this->normalizeAssetContent($content, $asset['key'], $asset['content_type']);
    
        if ($content === null) {
            throw new \Exception("Empty content for asset {$asset['key']}");
        }
    
        // Define o caminho de armazenamento correto
        $storagePath = "{$theme->store_id}/{$theme->shopify_theme_id}/{$asset['key']}";
        
        $checksum = md5($content);
    
        // Garante que o diretório existe
        Storage::disk('themes')->makeDirectory(dirname($storagePath));
        
        // Salva o conteúdo
        Storage::disk('themes')->put($storagePath, $content);
    
        // Atualiza ou cria o registro do asset
        ThemeAsset::updateOrCreate(
            [
                'theme_id' => $theme->id,
                'key' => $asset['key']
            ],
            [
                'content_type' => $asset['content_type'],
                'size' => strlen($content), // Calcula o tamanho real do conteúdo
                'checksum' => $checksum,
                'public_url' => $asset['public_url'],
                'storage_path' => $storagePath,
                'content' => null // Sempre armazena apenas no filesystem
            ]
        );
    
        return [
            'key' => $asset['key'],
            'checksum' => $checksum,
            'size' => strlen($content),
            'path' => $storagePath
        ];
    }

      /**
       * Normaliza o conteúdo do asset conforme seu tipo
       */
      protected function normalizeAssetContent($content, string $key, string $contentType)
    {
        // Se for null ou vazio, retorna como está
        if (empty($content)) {
            return $content;
        }

        // Verifica se é um SVG (por extensão ou content type)
        if (str_contains($key, '.svg') || str_contains($contentType, 'image/svg+xml')) {
            // Se o conteúdo parecer ser base64, decodifica para texto
            if (base64_encode(base64_decode($content, true)) === $content) {
                return base64_decode($content);
            }
            // Se já for texto XML, retorna como está
            return $content;
        }

        // Decodifica se for base64 para outros tipos de imagem
        if (is_string($content) && (
            base64_encode(base64_decode($content, true)) === $content ||
            str_contains($contentType, 'image/') ||
            str_contains($key, '.png') || 
            str_contains($key, '.jpg') ||
            str_contains($key, '.jpeg') ||
            str_contains($key, '.gif')
        )) {
            return base64_decode($content);
        }

        return $content;
    }

    /**
     * Busca o conteúdo de um asset específico
     */
    protected function fetchAssetContent(string $themeId, string $assetKey)
    {
        $baseUrl = "https://{$this->integration->shopify_domain}.myshopify.com/admin/api/{$this->apiVersion}/";
        $endpoint = "themes/{$themeId}/assets.json";
        $query = ['asset[key]' => $assetKey];

        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $this->integration->admin_token,
            'Accept' => 'application/json',
        ])->get($baseUrl . $endpoint, $query);

        if ($response->failed()) {
            throw new \Exception("Failed to fetch asset content: {$response->body()}");
        }

        return $response->json();
    }

    /**
     * Verifica se há próxima página na paginação
     */
    protected function hasNextPage($linkHeader): bool
    {
        return str_contains($linkHeader, 'rel="next"');
    }

    /**
     * Extrai o page_info do link header
     */
    protected function getNextPageInfo($linkHeader): ?string
    {
        if (preg_match('/<([^>]+)>; rel="next"/', $linkHeader, $matches)) {
            $url = $matches[1];
            parse_str(parse_url($url, PHP_URL_QUERY), $queryParams);
            return $queryParams['page_info'] ?? null;
        }
        return null;
    }
}