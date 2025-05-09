<?php

namespace App\Services\Objects;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ProductService
{
    /**
     * Get product by handle
     */
    public function getProductByHandle(string $handle, int $storeId)
    {
        // Cache key for this product
        $cacheKey = "product:{$storeId}:{$handle}";
        
        // Try to get from cache first
        return Cache::remember($cacheKey, 3600, function() use ($handle, $storeId) {
            try {
                // Find the product by handle and store_id
                $product = Product::where('handle', $handle)
                    ->where('store_id', $storeId)
                    ->first();
                
                if (!$product) {
                    Log::warning("Product not found", [
                        'handle' => $handle,
                        'store_id' => $storeId
                    ]);
                    return null;
                }
                
                // Enrich product data with variants, images, etc.
                return $this->enrichProductData($product);
            } catch (\Exception $e) {
                Log::error("Error fetching product", [
                    'handle' => $handle, 
                    'store_id' => $storeId,
                    'error' => $e->getMessage()
                ]);
                return null;
            }
        });
    }
    

    private function renderSection(string $sectionType, array $sectionData, Theme $theme, Context $context): string
{
    // Log for debugging
    \Log::debug("Iniciando renderização da seção: {$sectionType}", [
        'section_id' => $sectionData['id'] ?? 'unknown'
    ]);
    
    // Look up section file
    $sectionPath = "sections/{$sectionType}.liquid";
    $sectionContent = $this->getThemeFile($theme, $sectionPath);
    
    if (!$sectionContent) {
        // Section not found
        \Log::warning("Section file not found: {$sectionPath}");
        
        if (config('app.env') === 'development') {
            return "<!-- Section not found: {$sectionType} -->";
        }
        return '';
    }
            
    // Pre-processing
    $sectionContent = $this->removeThemeCheckTags($sectionContent);
    $sectionContent = $this->preprocessSectionContent($sectionContent, $sectionType, $theme);
    $sectionContent = $this->removeThemeCheckTags($sectionContent);
    
    try {
        // 1. Create a new context for the section
        $sectionContext = new Context();
        
        // 2. Preserve values from the original context
        foreach ($context->getAll() as $key => $value) {
            if ($key !== 'section') {
                $sectionContext->set($key, $value);
            }
        }
        
        // 3. Copy important registers from original context (including translations)
        foreach ($context->registers as $key => $value) {
            $sectionContext->registers[$key] = $value;
        }
        
        // 4. Process blocks and add to section data
        if (isset($sectionData['blocks']) && isset($sectionData['block_order'])) {
            // Create a blocks collection with both indexed and associative elements
            $processedBlocks = [];
            $blocksByIndex = [];
            
            // Add blocks in correct order with proper attributes
            foreach ($sectionData['block_order'] as $index => $blockId) {
                if (isset($sectionData['blocks'][$blockId])) {
                    $block = $sectionData['blocks'][$blockId];
                    $block['id'] = $blockId;
                    $block['shopify_attributes'] = "data-shopify-editor-block=\"{$blockId}\"";
                    
                    // Add to sequential array (for iteration)
                    $blocksByIndex[] = $block;
                    
                    // Also add to associative array (for direct access)
                    $processedBlocks[$blockId] = $block;
                }
            }
            
            // Set size property properly
            $size = count($blocksByIndex);
            $processedBlocks['size'] = $size;
            
            // Add helper properties that match Shopify's Liquid behavior
            if ($size > 0) {
                $processedBlocks['first'] = $blocksByIndex[0];
                $processedBlocks['last'] = $blocksByIndex[$size - 1];
                
                // Add all blocks as indexed elements so they can be accessed via for loop
                for ($i = 0; $i < $size; $i++) {
                    $processedBlocks[$i] = $blocksByIndex[$i];
                }
            }
            
            // Update section data with the processed blocks
            $sectionData['blocks'] = $processedBlocks;
            
        } else {
            // Initialize with empty blocks collection
            $sectionData['blocks'] = ['size' => 0];
        }
        
        // 5. Handle special case for 'block' snippet
        if (!Storage::disk('themes')->exists("{$theme->store_id}/{$theme->shopify_theme_id}/snippets/block.liquid") 
            && strpos($sectionContent, "{% render 'block'") !== false) {
            
            // Create a fallback implementation that processes block content directly
            $blockSnippetContent = '{{ block.shopify_attributes }}';
            
            // Temporarily add this snippet to the rendered context
            $sectionContext->set('block_snippet_content', $blockSnippetContent);
            
            // Replace render calls with our fallback content
            $sectionContent = preg_replace(
                '/\{%\s*render\s+\'block\'.*?%\}/', 
                '{{ block.shopify_attributes }}', 
                $sectionContent
            );
        }
        
        // 6. Set the section in context
        $sectionContext->set('section', $sectionData);
        
        // 7. Load schema and apply settings
        $this->loadAndApplySchema($sectionContext, $theme, $sectionType, $sectionData);
        
        // 8. Add global objects
        if (method_exists($this, 'globalObjectsProvider') && $this->globalObjectsProvider) {
            $request = request();
            $this->globalObjectsProvider->provide($sectionContext, [
                'request' => $request,
                'theme' => $theme,
                'template' => 'section',
                'section' => $sectionData
            ]);
        }
        
        // 9. Create template instance
        $template = clone $this->engine;
        
        // 10. Register standard filters
        $this->registerFilters($sectionContext);
        
        // 11. CRÍTICO: Registrar o filtro 't' diretamente nesta instância de template
        $template->registerFilter('t', function ($input, $params = []) use ($sectionContext) {
            // Skip non-string inputs
            if (!is_string($input)) {
                return $input;
            }
            
            // Remove t: prefix if present
            $key = strpos($input, 't:') === 0 ? substr($input, 2) : $input;
            
            // Garantir que há traduções disponíveis
            if (!isset($sectionContext->registers['translations']) || empty($sectionContext->registers['translations'])) {
                return $key;
            }
            
            $translations = $sectionContext->registers['translations'];
            $parts = explode('.', $key);
            
            // Seguir o caminho nas traduções
            $value = $translations;
            foreach ($parts as $part) {
                if (!isset($value[$part])) {
                    return $key; // Chave não encontrada, retornar original
                }
                $value = $value[$part];
            }
            
            // Retornar tradução se encontrada
            if (is_string($value)) {
                // Processar parâmetros
                if (!empty($params)) {
                    foreach ($params as $paramKey => $paramValue) {
                        $value = str_replace("{{ {$paramKey} }}", $paramValue, $value);
                    }
                }
                return $value;
            }
            
            return $key; // Retornar chave original se não encontrou uma string
        });
        
        // 12. Marcar filtro como registrado
        $sectionContext->registers['filters']['t'] = true;
        
        // 13. Parse e renderizar o template
        $template->parse($sectionContent);
        $renderedContent = $template->render($sectionContext->getAll());
        
        // 14. Post-processing
        if (strpos($renderedContent, '{{') !== false || strpos($renderedContent, '{%') !== false) {
            $renderedContent = $this->processUnprocessedLiquidTags($renderedContent, $sectionContext, $theme);
        }
        
        $renderedContent = $this->processAssetReferences($renderedContent, [
            'store_id' => $theme->store_id, 
            'theme_id' => $theme->shopify_theme_id
        ]);
        
        // 15. Wrap in section container
        return sprintf(
            '<div id="shopify-section-%s" class="shopify-section section section--%s" data-section-id="%s" data-section-type="%s">%s</div>',
            $sectionData['id'],
            $sectionType,
            $sectionData['id'],
            $sectionType,
            $renderedContent
        );
    } catch (\Exception $e) {
        \Log::error("Error rendering section {$sectionType}: " . $e->getMessage(), [
            'trace' => $e->getTraceAsString()
        ]);
        
        if (config('app.env') === 'local' || config('app.debug')) {
            return "<!-- Error rendering section {$sectionType}: " . htmlspecialchars($e->getMessage()) . " -->";
        }
        
        return '';
    }
}


/**
 * Load and apply schema data for sections
 */
private function loadAndApplySchema(Context $context, Theme $theme, string $sectionType, array $sectionData): void
{
    // Inicializar estrutura do schema no contexto
    $context->registers['section_schema'] = [];
    
    // Carregar o arquivo da seção
    $sectionPath = "sections/{$sectionType}.liquid";
    $sectionContent = $this->getThemeFile($theme, $sectionPath);
    
    // Se não encontrou o arquivo, não há schema para processar
    if (empty($sectionContent)) {
        \Log::warning("Não foi possível carregar o arquivo de seção: {$sectionPath}");
        return;
    }
    
    // Extrair o schema do conteúdo da seção
    if (preg_match('/{%\s*schema\s*%}(.*?){%\s*endschema\s*%}/s', $sectionContent, $matches)) {
        $schemaJson = trim($matches[1]);
        
        try {
            // Decodificar o JSON
            $schemaData = json_decode($schemaJson, true, 512, JSON_THROW_ON_ERROR);
            
            // Processar traduções no schema
            if ($this->translationService) {
                $schemaData = $this->translationService->processTranslations($schemaData, $context);
            }
            
            // Armazenar no contexto para acesso via {{ section_schema }}
            $context->set('section_schema', $schemaData);
            
            // Armazenar em registers para acesso via tags personalizadas
            $context->registers['section_schema'] = $schemaData;
            
            \Log::debug("Schema da seção {$sectionType} carregado com sucesso", [
                'has_settings' => isset($schemaData['settings']),
                'settings_count' => isset($schemaData['settings']) ? count($schemaData['settings']) : 0
            ]);
            
            // Processar configurações padrão do schema
            $this->applySchemaSettingsToContext($context, $schemaData, $sectionData);
            
        } catch (\JsonException $e) {
            \Log::error("Erro ao decodificar JSON do schema para seção {$sectionType}: " . $e->getMessage(), [
                'json_sample' => substr($schemaJson, 0, 200)
            ]);
        } catch (\Exception $e) {
            \Log::error("Erro ao processar schema para seção {$sectionType}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
        }
    } else {
        \Log::debug("Seção {$sectionType} não contém schema");
    }
}


private function applySchemaSettingsToContext(Context $context, array $schemaData, array $sectionData): void
{
    // Se não temos configurações no schema, não há nada a fazer
    if (!isset($schemaData['settings']) || !is_array($schemaData['settings'])) {
        return;
    }
    
    // Obter configurações atuais ou inicializar novo array
    $settings = $context->get('settings');
    if (!is_array($settings)) {
        $settings = [];
    }
    
    // Processar cada configuração
    foreach ($schemaData['settings'] as $setting) {
        if (!isset($setting['id'])) {
            continue;
        }
        
        $id = $setting['id'];
        
        // Verificar primeiro os dados da seção
        if (isset($sectionData['settings']) && isset($sectionData['settings'][$id])) {
            $settings[$id] = $sectionData['settings'][$id];
        } 
        // Depois tentar diretamente nos dados da seção (para compatibilidade)
        elseif (isset($sectionData[$id])) {
            $settings[$id] = $sectionData[$id];
        }
        // Por último, usar o valor padrão se disponível
        elseif (isset($setting['default']) && !isset($settings[$id])) {
            $settings[$id] = $setting['default'];
        }
    }
    
    // Verificar configurações em blocos
    if (isset($schemaData['blocks']) && isset($sectionData['blocks']) && isset($sectionData['block_order'])) {
        $blocks = [];
        
        // Processar cada bloco na ordem especificada
        foreach ($sectionData['block_order'] as $blockId) {
            if (!isset($sectionData['blocks'][$blockId])) {
                continue;
            }
            
            $blockData = $sectionData['blocks'][$blockId];
            $blockType = $blockData['type'] ?? '';
            
            // Pular blocos sem tipo
            if (empty($blockType)) {
                continue;
            }
            
            // Encontrar configurações para este tipo de bloco
            $blockSettingsSchema = null;
            foreach ($schemaData['blocks'] as $blockSchema) {
                if (($blockSchema['type'] ?? '') === $blockType) {
                    $blockSettingsSchema = $blockSchema['settings'] ?? [];
                    break;
                }
            }
            
            // Se encontramos o schema para este tipo de bloco, processar
            if ($blockSettingsSchema) {
                // Copiar os dados do bloco
                $block = $blockData;
                $block['id'] = $blockId;
                
                // Garantir que temos um array de configurações
                if (!isset($block['settings']) || !is_array($block['settings'])) {
                    $block['settings'] = [];
                }
                
                // Aplicar valores padrão às configurações ausentes
                foreach ($blockSettingsSchema as $blockSetting) {
                    if (isset($blockSetting['id']) && isset($blockSetting['default'])) {
                        $settingId = $blockSetting['id'];
                        
                        // Usar valor existente ou valor padrão
                        if (!isset($block['settings'][$settingId])) {
                            $block['settings'][$settingId] = $blockSetting['default'];
                        }
                    }
                }
                
                $blocks[] = $block;
            } else {
                // Adicionar o bloco mesmo sem schema
                $block = $blockData;
                $block['id'] = $blockId;
                $blocks[] = $block;
            }
        }
        
        // Adicionar blocos processados ao contexto
        if (!empty($blocks)) {
            $context->set('blocks', $blocks);
        }
    }
    
    // Atualizar configurações no contexto
    $context->set('settings', $settings);
    
    // Atualizar também nos registers para acesso via tags
    $context->registers['settings'] = $settings;
    
    \Log::debug("Configurações do schema aplicadas ao contexto", [
        'settings_count' => count($settings),
        'section_id' => $sectionData['id'] ?? 'unknown'
    ]);
}

/**
 * Função modular para pré-processamento de componentes com suporte para SVGs dinâmicos
 */
private function preprocessSectionContent(string $sectionContent, string $sectionType, Theme $theme): string
{
    // Log para debug
    \Log::debug("Pré-processando conteúdo da seção: {$sectionType}");
    
    // 1. Substituir referências a arquivos CSS
    $sectionContent = preg_replace_callback(
        '/\{\{\s*[\'"]?([^\'"}]*\.css)[\'"]?\s*\|\s*asset_url\s*\|\s*stylesheet_tag\s*\}\}/i',
        function($matches) use ($theme) {
            $cssFile = $matches[1];
            return '<link rel="stylesheet" href="' . url("assets/{$theme->store_id}/{$theme->shopify_theme_id}/{$cssFile}") . '" media="print" onload="this.media=\'all\'">';
        },
        $sectionContent
    );
    
    // 2. Substituir referências a placeholder_svg_tag usando método dinâmico
    $sectionContent = preg_replace_callback(
        '/\{\{\s*[\'"]?([^\'"}]*)[\'"]?\s*\|\s*placeholder_svg_tag(?:\s*:\s*[\'"]([^\'"]*)[\'"])?(?:\s*\}\})?/i',
        function($matches) use ($theme) {
            $svgName = trim($matches[1], "'\"");
            $class = isset($matches[2]) ? $matches[2] : '';
            
            return $this->generateSvgPlaceholder($svgName, $class, $theme);
        },
        $sectionContent
    );
    
    // 3. Substituir referências a inline_asset_content para SVGs
    $sectionContent = preg_replace_callback(
        '/\{\{-?\s*[\'"]?([^\'"}]*\.svg)[\'"]?\s*\|\s*inline_asset_content\s*-?\}\}/i',
        function($matches) use ($theme) {
            $svgFile = $matches[1];
            return $this->inlineSvgContent($svgFile, $theme);
        },
        $sectionContent
    );
    
    return $sectionContent;
}

    /**
     * Enrich product data with related information
     */
    private function enrichProductData(Product $product)
    {
        // Convert to array for easier manipulation
        $productData = $product->toArray();
        
        try {
            // Load variants
            $variants = $this->getProductVariants($product->id);
            $productData['variants'] = $variants;
            
            // Set first variant as selected_or_first_available_variant (Shopify convention)
            $productData['selected_or_first_available_variant'] = $variants[0] ?? null;
            
            // Load images
            $images = $this->getProductImages($product->id);
            $productData['images'] = $images;
            $productData['featured_image'] = $images[0]['src'] ?? null;
            
            // Load options
            $options = $this->getProductOptions($product->id);
            $productData['options'] = $options;
            
            // Format prices for display
            if (isset($productData['price'])) {
                $productData['price_formatted'] = 'R$ ' . number_format($productData['price'], 2, ',', '.');
            }
            
            if (isset($productData['compare_at_price']) && $productData['compare_at_price'] > 0) {
                $productData['compare_at_price_formatted'] = 'R$ ' . number_format($productData['compare_at_price'], 2, ',', '.');
            }
            
            // Add additional Shopify-compatible properties
            $productData['url'] = '/produtos/' . $product->handle;
            $productData['type'] = $product->product_type;
            
            return $productData;
        } catch (\Exception $e) {
            Log::error("Error enriching product data", [
                'product_id' => $product->id,
                'error' => $e->getMessage()
            ]);
            
            // Return basic product data if enrichment fails
            return $productData;
        }
    }
    
    /**
     * Get product variants
     */
    private function getProductVariants(int $productId): array
    {
        try {
            // Fetch variants from database
            $variants = DB::table('product_variants')
                ->where('product_id', $productId)
                ->get();
            
            $result = [];
            foreach ($variants as $variant) {
                $variantData = (array) $variant;
                
                // Format prices
                if (isset($variantData['price'])) {
                    $variantData['price_formatted'] = 'R$ ' . number_format($variantData['price'], 2, ',', '.');
                }
                
                if (isset($variantData['compare_at_price']) && $variantData['compare_at_price'] > 0) {
                    $variantData['compare_at_price_formatted'] = 'R$ ' . number_format($variantData['compare_at_price'], 2, ',', '.');
                }
                
                $result[] = $variantData;
            }
            
            return $result;
        } catch (\Exception $e) {
            Log::error("Error fetching product variants", [
                'product_id' => $productId,
                'error' => $e->getMessage()
            ]);
            
            return [];
        }
    }
    
    /**
     * Get product images
     */
    private function getProductImages(int $productId): array
    {
        try {
            // Fetch images from database
            $images = DB::table('product_images')
                ->where('product_id', $productId)
                ->orderBy('position')
                ->get();
            
            $result = [];
            foreach ($images as $image) {
                $result[] = [
                    'id' => $image->id,
                    'product_id' => $image->product_id,
                    'position' => $image->position,
                    'src' => $image->src,
                    'width' => $image->width,
                    'height' => $image->height,
                    'alt' => $image->alt
                ];
            }
            
            return $result;
        } catch (\Exception $e) {
            Log::error("Error fetching product images", [
                'product_id' => $productId,
                'error' => $e->getMessage()
            ]);
            
            return [];
        }
    }
    
    /**
     * Get product options
     */
    private function getProductOptions(int $productId): array
    {
        try {
            // Fetch options from database
            $options = DB::table('product_options')
                ->where('product_id', $productId)
                ->orderBy('position')
                ->get();
            
            $result = [];
            foreach ($options as $option) {
                // Fetch option values
                $values = DB::table('product_option_values')
                    ->where('option_id', $option->id)
                    ->orderBy('position')
                    ->pluck('value')
                    ->toArray();
                
                $result[] = [
                    'id' => $option->id,
                    'product_id' => $option->product_id,
                    'name' => $option->name,
                    'position' => $option->position,
                    'values' => $values
                ];
            }
            
            return $result;
        } catch (\Exception $e) {
            Log::error("Error fetching product options", [
                'product_id' => $productId,
                'error' => $e->getMessage()
            ]);
            
            return [];
        }
    }
    
    /**
     * Get related products
     */
    public function getRelatedProducts(int $productId, int $storeId, int $limit = 4): array
    {
        try {
            // Fetch the product to get its type and collection IDs
            $product = Product::find($productId);
            
            if (!$product) {
                return [];
            }
            
            // First try to get products from the same collections
            $relatedProducts = DB::table('products as p')
                ->join('collection_products as cp', 'p.id', '=', 'cp.product_id')
                ->whereIn('cp.collection_id', function($query) use ($productId) {
                    $query->select('collection_id')
                        ->from('collection_products')
                        ->where('product_id', $productId);
                })
                ->where('p.id', '!=', $productId) // Exclude the current product
                ->where('p.store_id', $storeId)
                ->where('p.status', 'active')
                ->select('p.*')
                ->distinct()
                ->limit($limit)
                ->get();
            
            // If we don't have enough related products, add some from the same product type
            if ($relatedProducts->count() < $limit && !empty($product->product_type)) {
                $additionalCount = $limit - $relatedProducts->count();
                
                $additionalProducts = DB::table('products')
                    ->where('id', '!=', $productId) // Exclude the current product
                    ->where('store_id', $storeId)
                    ->where('product_type', $product->product_type)
                    ->where('status', 'active')
                    ->whereNotIn('id', $relatedProducts->pluck('id')->toArray()) // Exclude already found products
                    ->limit($additionalCount)
                    ->get();
                
                // Merge the results
                $relatedProducts = $relatedProducts->concat($additionalProducts);
            }
            
            // If we still don't have enough, add some random products
            if ($relatedProducts->count() < $limit) {
                $additionalCount = $limit - $relatedProducts->count();
                
                $additionalProducts = DB::table('products')
                    ->where('id', '!=', $productId) // Exclude the current product
                    ->where('store_id', $storeId)
                    ->where('status', 'active')
                    ->whereNotIn('id', $relatedProducts->pluck('id')->toArray()) // Exclude already found products
                    ->inRandomOrder()
                    ->limit($additionalCount)
                    ->get();
                
                // Merge the results
                $relatedProducts = $relatedProducts->concat($additionalProducts);
            }
            
            // Enrich the products data
            $enrichedProducts = [];
            foreach ($relatedProducts as $relatedProduct) {
                // Convert to Product model to use enrichProductData
                $productModel = new Product((array) $relatedProduct);
                $productModel->exists = true;
                
                $enrichedProducts[] = $this->enrichProductData($productModel);
            }
            
            return $enrichedProducts;
        } catch (\Exception $e) {
            Log::error("Error fetching related products", [
                'product_id' => $productId,
                'store_id' => $storeId,
                'error' => $e->getMessage()
            ]);
            
            return [];
        }
    }
    
    /**
     * Search products by name, handle, SKU, or description
     */
    public function searchProducts(string $query, int $storeId, int $limit = 10): array
    {
        try {
            $products = Product::where('store_id', $storeId)
                ->where('status', 'active')
                ->where(function($q) use ($query) {
                    $q->where('title', 'LIKE', "%{$query}%")
                      ->orWhere('handle', 'LIKE', "%{$query}%")
                      ->orWhere('description', 'LIKE', "%{$query}%");
                })
                ->limit($limit)
                ->get();
            
            // Enrich the products data
            $enrichedProducts = [];
            foreach ($products as $product) {
                $enrichedProducts[] = $this->enrichProductData($product);
            }
            
            return $enrichedProducts;
        } catch (\Exception $e) {
            Log::error("Error searching products", [
                'query' => $query,
                'store_id' => $storeId,
                'error' => $e->getMessage()
            ]);
            
            return [];
        }
    }
}