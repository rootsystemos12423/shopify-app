<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Liquid\{Template, LocalFileSystem};
use App\Models\{Theme, Domain, Product};
use Liquid\Context;

// Essential services
use App\Services\ShopifyContextFactory;
use App\Services\TranslationService;
use App\Services\ThemeSettingsService;
use App\Services\GlobalObjectsProvider;
use App\Services\ThemeAssetManager;
use App\Services\ThemeTranslationManager;
use App\Services\ThemeTemplateRenderer;
use App\Services\ThemeSchemaManager;
use App\Services\ThemeContextManager;
use App\Services\ThemeErrorHandler;
use App\Services\ThemeContentService;
use App\Services\LiquidTagsAndFiltersManager;
use App\Services\Objects\ProductService;

class ProductController extends Controller
{
    /*****************************************************************
     *                    CONSTANTS & PROPERTIES
     *****************************************************************/

    /**
     * Maximum rendering depth
     */
    const MAX_RENDER_DEPTH = 10;
    
    /**
     * Maximum content size
     */
    const MAX_CONTENT_SIZE = 2000000; // 2MB

    /**
     * @var Template Liquid engine instance
     */
    protected Template $engine;

    /**
     * @var ShopifyContextFactory
     */
    protected $shopifyContextFactory;

    /**
     * @var TranslationService
     */
    protected $translationService;

    /**
     * @var ThemeSettingsService
     */
    protected $themeSettingsService;

    /**
     * @var GlobalObjectsProvider
     */
    protected $globalObjectsProvider;

    /**
     * @var ThemeAssetManager
     */
    protected $themeAssetManager;

    /**
     * @var ThemeTranslationManager
     */
    protected $themeTranslationManager;

    /**
     * @var ThemeTemplateRenderer
     */
    protected $themeTemplateRenderer;

    /**
     * @var ThemeSchemaManager
     */
    protected $themeSchemaManager;

    /**
     * @var ThemeContextManager
     */
    protected $themeContextManager;

    /**
     * @var ThemeErrorHandler
     */
    protected $themeErrorHandler;

    /**
     * @var ThemeContentService
     */
    protected $themeContentService;

    /**
     * @var LiquidTagsAndFiltersManager
     */
    protected $liquidTagsAndFiltersManager;
    
    /**
     * @var ProductService
     */
    protected $productService;

    /*****************************************************************
     *                        INITIALIZATION
     *****************************************************************/

    /**
     * Constructor
     */
    public function __construct(
        ShopifyContextFactory $shopifyContextFactory,
        TranslationService $translationService,
        ThemeSettingsService $themeSettingsService,
        GlobalObjectsProvider $globalObjectsProvider,
        ThemeAssetManager $themeAssetManager,
        ThemeTranslationManager $themeTranslationManager,
        ThemeTemplateRenderer $themeTemplateRenderer,
        ThemeSchemaManager $themeSchemaManager,
        ThemeContextManager $themeContextManager,
        ThemeErrorHandler $themeErrorHandler,
        ThemeContentService $themeContentService,
        LiquidTagsAndFiltersManager $liquidTagsAndFiltersManager,
        ProductService $productService
    ) {
        $this->shopifyContextFactory = $shopifyContextFactory;
        $this->translationService = $translationService;
        $this->themeSettingsService = $themeSettingsService;
        $this->globalObjectsProvider = $globalObjectsProvider;
        $this->themeAssetManager = $themeAssetManager;
        $this->themeTranslationManager = $themeTranslationManager;
        $this->themeTemplateRenderer = $themeTemplateRenderer;
        $this->themeSchemaManager = $themeSchemaManager;
        $this->themeContextManager = $themeContextManager;
        $this->themeErrorHandler = $themeErrorHandler;
        $this->themeContentService = $themeContentService;
        $this->liquidTagsAndFiltersManager = $liquidTagsAndFiltersManager;
        $this->productService = $productService;
    }

    /**
     * Initialize the Liquid engine
     */
    private function initLiquidEngine(Theme $theme): void
    {
        $this->engine = new Template();
        $this->engine->setFileSystem(new LocalFileSystem(
            storage_path("app/themes/{$theme->store_id}/{$theme->shopify_theme_id}")
        ));

        // Register tags using the service
        $this->liquidTagsAndFiltersManager->registerTags($this->engine);
    }

    /*****************************************************************
     *                        MAIN ENDPOINTS
     *****************************************************************/

    /**
     * Endpoint to render a product page
     */
    public function renderProduct(Request $request, $handle = null)
    {
        try {
            $path = $request->path();
            $productHandle = $handle ?: $this->extractProductHandle($path);
            
            if (empty($productHandle)) {
                throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException("Product handle not found");
            }
            
            // Use context manager to get shop and theme
            $shop = $this->themeContextManager->getShop($request);
            $theme = $this->themeContextManager->getTheme($shop);
            
            // Get product data
            $product = $this->productService->getProductByHandle($productHandle, $shop->store_id);
            
            if (!$product) {
                throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException("Product not found: {$productHandle}");
            }
            
            // Build the base context with product data
            $baseContext = $this->buildProductContext($request, $theme, $product);
            $context = $this->themeContextManager->createContext($baseContext);
            
            // Initialize the Liquid engine
            $this->initLiquidEngine($theme);
            
            // Add global objects to context
            $this->themeContextManager->addGlobalObjects(
                $context, 
                $this->globalObjectsProvider, 
                $request, 
                $theme, 
                'product',
                ['product' => $product]
            );
            
            // Initialize translations
            $this->themeTranslationManager->initializeTranslations($context, $theme, $request->query('locale'));
            
            // Initialize theme settings
            $this->initializeThemeSettings($context, $theme, $request->query('locale'));
            
            \Log::debug("Rendering product template for: {$productHandle}");
            
            // Generate content using the content service
            $pageContent = $this->generateProductContent($path, $theme, $context, $product);
            \Log::debug("Product page content generated: " . (empty($pageContent) ? "EMPTY" : "FILLED"), [
                'content_length' => strlen($pageContent)
            ]);
            
            // Set content_for_layout in context before rendering layout
            $context->set('content_for_layout', $pageContent);
            
            // Render the layout with the generated content
            $layoutContent = $this->renderLayout($theme, $pageContent, $context);
            
            // Finalize and return the content
            return response($this->themeTemplateRenderer->finalizeOutput(
                $layoutContent, 
                $context->getAll(),
                $this->themeTranslationManager
            ))->header('Content-Type', 'text/html; charset=UTF-8');
            
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            \Log::warning("Product not found: " . $e->getMessage());
            return $this->handle404($request);
        } catch (\Throwable $e) {
            \Log::error("Error rendering product template: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->themeErrorHandler->handleRenderError($e, $request);
        }
    }

    /**
     * Extract product handle from URL path
     */
    private function extractProductHandle(string $path): string
    {
        $path = trim($path, '/');
        
        // Check if path starts with 'products/'
        if (Str::startsWith($path, 'products/')) {
            // Extract the handle part
            return Str::after($path, 'products/');
        }
        
        return '';
    }

    /**
     * Generate content for product page
     */
    private function generateProductContent(string $path, Theme $theme, Context $context, $product): string
    {
        \Log::debug("Generating product content for path: {$path}");
        
        // First try JSON template
        $jsonPath = "templates/product.json";
        $jsonContent = $this->themeContentService->getThemeFile($theme, $jsonPath);
        
        if ($jsonContent && $this->isValidJson($jsonContent)) {
            \Log::debug("Product JSON template found and valid");
            $templateData = json_decode($jsonContent, true);
            
            if (isset($templateData['sections']) && isset($templateData['order'])) {
                \Log::debug("Rendering product JSON template with " . count($templateData['sections']) . " sections");
                
                // Ensure translations are loaded
                $this->ensureProductContextEnriched($context, $product);
                
                $renderedContent = $this->renderJsonTemplate($templateData, $theme, $context);
                
                // Process asset references
                $renderedContent = $this->themeAssetManager->processAssetReferences($renderedContent, [
                    'store_id' => $theme->store_id, 
                    'theme_id' => $theme->shopify_theme_id
                ]);
                
                return $renderedContent;
            }
        }
        
        // Fallback to Liquid template
        $liquidPath = "templates/product.liquid";
        $liquidContent = $this->themeContentService->getThemeFile($theme, $liquidPath);
        
        if ($liquidContent) {
            \Log::debug("Product Liquid template found");
            
            // Ensure product context is enriched
            $this->ensureProductContextEnriched($context, $product);
            
            $renderedContent = $this->themeTemplateRenderer->renderTemplateContent($liquidContent, $context);
            
            // Process asset references
            $renderedContent = $this->themeAssetManager->processAssetReferences($renderedContent, [
                'store_id' => $theme->store_id, 
                'theme_id' => $theme->shopify_theme_id
            ]);
            
            return $renderedContent;
        }
        
        // Fallback for no template
        \Log::warning("No product template found. Using default template.");
        
        return $this->generateDefaultProductTemplate($product);
    }

    /**
     * Generate a default product template when no template is found
     */
    private function generateDefaultProductTemplate($product): string
    {
        return "
            <div class='container product-page'>
                <div class='row'>
                    <div class='col-md-6'>
                        <div class='product-images'>
                            <img src='" . ($product->featured_image ?? '/img/no-image.jpg') . "' class='img-fluid' alt='" . htmlspecialchars($product->title) . "'>
                        </div>
                    </div>
                    <div class='col-md-6'>
                        <h1>" . htmlspecialchars($product->title) . "</h1>
                        <div class='product-price'>
                            " . $this->formatPrice($product->price) . "
                        </div>
                        <div class='product-description'>
                            " . $product->description . "
                        </div>
                        <div class='product-actions'>
                            <form action='/carrinho/add' method='post'>
                                <input type='hidden' name='id' value='" . $product->id . "'>
                                <div class='form-group'>
                                    <label for='quantity'>Quantidade</label>
                                    <input type='number' id='quantity' name='quantity' value='1' min='1' class='form-control'>
                                </div>
                                <button type='submit' class='btn btn-primary'>Adicionar ao Carrinho</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        ";
    }

    /**
     * Format price for display
     */
    private function formatPrice($price): string
    {
        return 'R$ ' . number_format((float)$price, 2, ',', '.');
    }

    /**
     * Build the context for product rendering
     */
    private function buildProductContext(Request $request, Theme $theme, $product): array
    {
        return [
            'shop' => [
                'id' => $theme->store_id,
                'name' => $theme->store->name,
                'domain' => $theme->store->domain,
                'currency' => 'BRL',
                'money_format' => 'R$ {{amount}}',
                'settings' => $theme->settings ?? []
            ],
            'theme' => [
                'id' => $theme->shopify_theme_id,
                'store_id' => $theme->store_id,
                'name' => $theme->name,
                'role' => $theme->role
            ],
            'request' => [
                'path' => $request->path(),
                'query' => $request->query()
            ],
            'theme_schema' => [],
            'content_for_header' => $this->themeContentService->generateContentForHeader($theme, $request),
            'template' => 'product',
            'product' => $product,
        ];
    }

    /**
     * Handle 404 errors
     */
    private function handle404(Request $request)
    {
        // Try to render 404 template if available
        try {
            $shop = $this->themeContextManager->getShop($request);
            $theme = $this->themeContextManager->getTheme($shop);
            
            // Check for 404 template
            $liquidPath = "templates/404.liquid";
            $liquidContent = $this->themeContentService->getThemeFile($theme, $liquidPath);
            
            if ($liquidContent) {
                \Log::debug("Found 404 template, rendering themed 404 page");
                
                // Create context with 404-specific values
                $baseContext = [
                    'shop' => [
                        'id' => $theme->store_id,
                        'name' => $theme->store->name,
                        'domain' => $theme->store->domain,
                        'currency' => 'BRL',
                        'money_format' => 'R$ {{amount}}',
                        'settings' => $theme->settings ?? []
                    ],
                    'theme' => [
                        'id' => $theme->shopify_theme_id,
                        'store_id' => $theme->store_id,
                        'name' => $theme->name,
                        'role' => $theme->role
                    ],
                    'request' => [
                        'path' => $request->path(),
                        'query' => $request->query()
                    ],
                    'theme_schema' => [],
                    'content_for_header' => $this->themeContentService->generateContentForHeader($theme, $request),
                    'template' => '404',
                ];
                
                $context = $this->themeContextManager->createContext($baseContext);
                
                // Initialize the Liquid engine
                $this->initLiquidEngine($theme);
                
                // Add global objects to context
                $this->themeContextManager->addGlobalObjects(
                    $context, 
                    $this->globalObjectsProvider, 
                    $request, 
                    $theme, 
                    '404'
                );
                
                // Initialize translations
                $this->themeTranslationManager->initializeTranslations($context, $theme, $request->query('locale'));
                
                // Initialize theme settings
                $this->initializeThemeSettings($context, $theme, $request->query('locale'));
                
                // Render 404 template
                $pageContent = $this->themeTemplateRenderer->renderTemplateContent($liquidContent, $context);
                $layoutContent = $this->renderLayout($theme, $pageContent, $context);
                
                return response($this->themeTemplateRenderer->finalizeOutput(
                    $layoutContent, 
                    $context->getAll(),
                    $this->themeTranslationManager
                ), 404)->header('Content-Type', 'text/html; charset=UTF-8');
            } else {
                \Log::warning("No 404 template found in theme, using default 404 page");
            }
        } catch (\Throwable $e) {
            \Log::error("Error rendering 404 template: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
        }
        
        // If all else fails, redirect to the ThemeController to handle the 404
        return redirect('/404');
    }

    /*****************************************************************
     *                     HELPER METHODS
     *****************************************************************/

    /**
     * Ensure product context is properly enriched
     */
    /**
     * Ensure product context is properly enriched with all media data needed for the viewer
     */
    private function ensureProductContextEnriched(Context $context, $product): void
{
    // Estrutura básica do produto enriquecido
    $enrichedProduct = [
        'id' => $product['id'] ?? null,
        'title' => $product['title'] ?? '',
        'handle' => $product['handle'] ?? '',
        'description' => $product['body_html'] ?? '',
        'price' => $product['price'] ?? 10.0,
        'vendor' => $product['vendor'] ?? '',
        
        // Processamento de mídia corretamente estruturado
        'media' => $this->processProductMedia($product),
        'featured_media' => $this->getProductFeaturedMedia($product),
        'images' => $this->processProductImages($product),
        'featured_image' => $this->getProductFeaturedImage($product),
        
        // Variantes com mídia associada
        'variants' => $this->processProductVariants($product),
        'selected_or_first_available_variant' => 
            $product['selected_or_first_available_variant'] ?? 
            $product['selected_variant'] ?? 
            $this->getFirstAvailableVariant($product),
    ];
    
    // Define media_count para uso na galeria
    $enrichedProduct['media_count'] = is_array($enrichedProduct['media']) ? count($enrichedProduct['media']) : 0;
    
    // Determina has_only_default_variant
    $enrichedProduct['has_only_default_variant'] = $this->hasOnlyDefaultVariant($enrichedProduct['variants']);
    
    // Processa dados aninhados JSON
    $this->processNestedJsonData($enrichedProduct);
    
    // Define o produto no contexto
    $context->set('product', $enrichedProduct);
    
    // Define media e verifica que todos os itens têm media_type correto
    $mediaItems = $enrichedProduct['media'];
    foreach ($mediaItems as &$mediaItem) {
        // Garante que todos os media_type são explicitamente definidos
        if (!isset($mediaItem['media_type']) || empty($mediaItem['media_type'])) {
            // Determina o tipo com base no media_content_type
            if (isset($mediaItem['media_content_type'])) {
                if (strpos($mediaItem['media_content_type'], 'image/') === 0) {
                    $mediaItem['media_type'] = 'image';
                } elseif (strpos($mediaItem['media_content_type'], 'video/') === 0) {
                    $mediaItem['media_type'] = 'video';
                } else {
                    // Default para image se não for possível determinar
                    $mediaItem['media_type'] = 'image';
                }
            } else {
                // Se não tiver media_content_type, assume que é uma imagem
                $mediaItem['media_type'] = 'image';
            }
        }
        
        // Verifica e corrige IDs vazios
        if (!isset($mediaItem['id']) || empty($mediaItem['id'])) {
            $mediaItem['id'] = uniqid('media_');
        }
    }
    $context->set('media', $mediaItems);

    // Define variantes específicas para o contexto da galeria
    $context->set('variant_images', $this->getVariantImages($enrichedProduct));
    
    // ADICIONANDO CONFIGURAÇÕES DE SEÇÃO FALTANTES
    $this->ensureSectionSettingsForGallery($context);
    
    // CALCULANDO VARIÁVEIS DERIVADAS USADAS NO TEMPLATE
    $this->calculateDerivedGalleryVariables($context, $enrichedProduct);
    
    // Define outras propriedades do contexto para suporte à galeria
    $this->setAdditionalProductContext($context, $enrichedProduct);
    
    // Garante que as traduções estejam carregadas
    $this->ensureTranslationsLoaded($context);
}

/**
 * Calcula variáveis derivadas usadas no template da galeria
 */
private function calculateDerivedGalleryVariables(Context $context, $product): void
{
    $section = $context->get('section');
    $variantImages = $context->get('variant_images') ?? [];
    $mediaCount = $product['media_count'] ?? 0;
    
    // Calcula single_media_visible
    $singleMediaVisible = false;
    if (
        ($section['settings']['hide_variants'] && count($variantImages) == $mediaCount) ||
        $mediaCount == 1
    ) {
        $singleMediaVisible = true;
    }
    
    // Calcula single_media_visible_mobile
    $singleMediaVisibleMobile = ($mediaCount == 1 || $singleMediaVisible);
    
    // Calcula hide_mobile_slider
    $hideMobileSlider = false;
    if (
        $mediaCount == 0 || 
        $singleMediaVisibleMobile || 
        $section['settings']['mobile_thumbnails'] == 'show' || 
        ($section['settings']['mobile_thumbnails'] == 'columns' && $mediaCount < 3)
    ) {
        $hideMobileSlider = true;
    }
    
    // Calcula media_width com base na configuração media_size
    $mediaWidth = 0.55; // default medium
    if ($section['settings']['media_size'] == 'large') {
        $mediaWidth = 0.65;
    } elseif ($section['settings']['media_size'] == 'small') {
        $mediaWidth = 0.45;
    }
    
    // Calcula is_not_limited_to_single_item
    $isNotLimitedToSingleItem = true;
    $limit = $context->get('limit');
    if ($limit && $limit == 1) {
        $isNotLimitedToSingleItem = false;
    }
    
    // Define as variáveis calculadas no contexto
    $context->set('single_media_visible', $singleMediaVisible);
    $context->set('single_media_visible_mobile', $singleMediaVisibleMobile);
    $context->set('hide_mobile_slider', $hideMobileSlider);
    $context->set('media_width', $mediaWidth);
    $context->set('is_not_limited_to_single_item', $isNotLimitedToSingleItem);
}

/**
 * Certifica que todas as configurações de seção necessárias para a galeria estejam presentes
 */
private function ensureSectionSettingsForGallery(Context $context): void
{
    // Se section já existe, use-a, caso contrário crie uma nova
    $section = $context->get('section') ?? [];
    
    // Se settings não existe ou não é array, inicialize
    if (!isset($section['settings']) || !is_array($section['settings'])) {
        $section['settings'] = [];
    }
    
    // Configurações padrão da galeria de mídia
    $defaultGallerySettings = [
        'gallery_layout' => 'thumbnail_slider', // Opções: stacked, grid, thumbnail_slider
        'media_size' => 'medium',              // Opções: small, medium, large
        'media_fit' => 'contain',             // Opções: contain, cover
        'hide_variants' => false,             // Esconder imagens de variantes duplicadas
        'enable_video_looping' => true,       // Loop em vídeos
        'enable_sticky_info' => true,         // Informação fixa enquanto rola
        'mobile_thumbnails' => 'hide',        // Opções: hide, show, columns
        'constrain_to_viewport' => true,      // Limitar tamanho da mídia à janela de visualização
        'image_zoom' => 'lightbox',           // Opções: lightbox, hover, disabled
    ];
    
    // Mescla configurações padrão com existentes
    foreach ($defaultGallerySettings as $key => $defaultValue) {
        if (!isset($section['settings'][$key])) {
            $section['settings'][$key] = $defaultValue;
        }
    }
    
    // Atualiza section no contexto
    $context->set('section', $section);
}


   /**
 * Processa a mídia do produto com estrutura completa e consistente
 */
private function processProductMedia($product): array
{
    $media = [];
    
    // If we already have structured media, use it directly but ensure complete properties
    if (isset($product['media']) && is_array($product['media'])) {
        $media = $product['media'];
        
        // Check and complete missing properties for each item
        foreach ($media as &$mediaItem) {
            // ID is required
            if (!isset($mediaItem['id'])) {
                $mediaItem['id'] = uniqid('media_');
            }
            
            // Position is required
            if (!isset($mediaItem['position'])) {
                $mediaItem['position'] = 1;
            }
            
            // media_type is required - IMPORTANT FIX HERE
            if (!isset($mediaItem['media_type'])) {
                // Default to image if not specified
                $mediaItem['media_type'] = 'image';
            }
            
            // media_content_type is required
            if (!isset($mediaItem['media_content_type'])) {
                // Determine media_content_type based on media_type
                switch ($mediaItem['media_type']) {
                    case 'image':
                        $mediaItem['media_content_type'] = 'image/jpg';
                        break;
                    case 'video':
                        $mediaItem['media_content_type'] = 'video/mp4';
                        break;
                    case 'external_video':
                        $mediaItem['media_content_type'] = 'external_video';
                        break;
                    case 'model':
                        $mediaItem['media_content_type'] = 'model/gltf-binary';
                        break;
                    default:
                        $mediaItem['media_content_type'] = 'image/jpg';
                }
            }
            
            // Ensure preview_image for any media type
            if (!isset($mediaItem['preview_image']) && isset($mediaItem['src'])) {
                $mediaItem['preview_image'] = [
                    'src' => $mediaItem['src'],
                    'alt' => $mediaItem['alt'] ?? '',
                    'width' => $mediaItem['width'] ?? 1000,
                    'height' => $mediaItem['height'] ?? 1000,
                    'aspect_ratio' => ($mediaItem['width'] ?? 1000) / ($mediaItem['height'] ?? 1000),
                ];
            }
            
            // Add aspect_ratio if missing
            if (isset($mediaItem['preview_image']) && !isset($mediaItem['preview_image']['aspect_ratio'])) {
                $width = $mediaItem['preview_image']['width'] ?? 1000;
                $height = $mediaItem['preview_image']['height'] ?? 1000;
                $mediaItem['preview_image']['aspect_ratio'] = $width / $height;
            }
            
            // Add aspect_ratio to media item if missing
            if (!isset($mediaItem['aspect_ratio']) && isset($mediaItem['preview_image']['aspect_ratio'])) {
                $mediaItem['aspect_ratio'] = $mediaItem['preview_image']['aspect_ratio'];
            }
            
            // Ensure that video types have proper host/external_id
            if ($mediaItem['media_type'] === 'external_video' && !isset($mediaItem['host'])) {
                $mediaItem['host'] = $this->determineVideoHost($mediaItem['src'] ?? '');
            }
            
            if ($mediaItem['media_type'] === 'external_video' && !isset($mediaItem['external_id'])) {
                $mediaItem['external_id'] = $this->extractVideoId($mediaItem['src'] ?? '', $mediaItem['host']);
            }
        }
    } 
    // If we only have images, convert them to the COMPLETE media format
    else if (isset($product['images']) && is_array($product['images'])) {
        foreach ($product['images'] as $index => $image) {
            $media[] = $this->createMediaFromImage($image, 'img_' . ($index + 1));
        }
    }
    
    // Sort media by position
    usort($media, function($a, $b) {
        $posA = $a['position'] ?? 999;
        $posB = $b['position'] ?? 999;
        return $posA - $posB;
    });
    
    return $media;
}

/**
 * Determina o tipo de host de vídeo com base na URL
 */
private function determineVideoHost($url) {
    if (strpos($url, 'youtube.com') !== false || strpos($url, 'youtu.be') !== false) {
        return 'youtube';
    } elseif (strpos($url, 'vimeo.com') !== false) {
        return 'vimeo';
    }
    
    // Default
    return 'youtube';
}

    /**
     * Get featured media for product
     */
    private function getProductFeaturedMedia($product)
    {
        // Se já temos featured_media
        if (isset($product['featured_media']) && !empty($product['featured_media'])) {
            $media = $product['featured_media'];
            
            // Adicionar media_content_type se não existir
            if (!isset($media['media_content_type']) && isset($media['media_type'])) {
                if ($media['media_type'] === 'image') {
                    $media['media_content_type'] = 'image/jpg';
                }
            }
            
            return $media;
        }
        
        // Se temos featured_image, convertê-la para formato de mídia
        if (isset($product['featured_image']) && !empty($product['featured_image'])) {
            $image = is_array($product['featured_image']) ? 
                $product['featured_image'] : 
                ['src' => $product['featured_image']];
            
            $contentType = 'image/jpg'; // Default
            if (isset($image['src'])) {
                $extension = pathinfo($image['src'], PATHINFO_EXTENSION);
                if (strtolower($extension) === 'png') {
                    $contentType = 'image/png';
                } elseif (strtolower($extension) === 'gif') {
                    $contentType = 'image/gif';
                }
            }
                
            return [
                'id' => $image['id'] ?? 'featured_img',
                'position' => 1,
                'media_type' => 'image',
                'media_content_type' => $contentType, // Crucial!
                'preview_image' => $image,
                'alt' => $image['alt'] ?? '',
                'src' => $image['src'] ?? '',
            ];
        }
        
        // Usar primeira mídia/imagem da coleção
        $media = $this->processProductMedia($product);
        return !empty($media) ? $media[0] : null;
    }


    /**
 * Extrai o ID do vídeo de uma URL com base no host
 */
private function extractVideoId($url, $host) {
    if ($host === 'youtube') {
        // Padrões para URLs do YouTube
        $patterns = [
            '/youtube\.com\/watch\?v=([^&\s]+)/', // youtube.com/watch?v=ID
            '/youtu\.be\/([^&\s]+)/',            // youtu.be/ID
            '/youtube\.com\/embed\/([^&\s]+)/'   // youtube.com/embed/ID
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }
    } elseif ($host === 'vimeo') {
        // Padrão para URLs do Vimeo
        if (preg_match('/vimeo\.com\/([0-9]+)/', $url, $matches)) {
            return $matches[1];
        }
    }
    
    // Fallback: retorna parte da URL após a última barra
    $parts = explode('/', $url);
    return end($parts);
}

    /**
     * Process product images for gallery display
     */
    private function processProductImages($product): array
    {
        $images = [];
        
        // Se já temos images estruturadas, usar diretamente
        if (isset($product['images']) && is_array($product['images'])) {
            $images = $product['images'];
        } 
        // Se temos apenas media, extrair imagens
        else if (isset($product['media']) && is_array($product['media'])) {
            foreach ($product['media'] as $item) {
                if ($item['media_type'] === 'image') {
                    $images[] = $item['preview_image'] ?? [
                        'id' => $item['id'] ?? '',
                        'src' => $item['src'] ?? '',
                        'alt' => $item['alt'] ?? '',
                        'position' => $item['position'] ?? 1,
                    ];
                }
            }
        }
        
        // Ordenar imagens por posição
        usort($images, function($a, $b) {
            $posA = $a['position'] ?? 999;
            $posB = $b['position'] ?? 999;
            return $posA - $posB;
        });
        
        return $images;
    }

    /**
     * Get featured image for product
     */
    private function getProductFeaturedImage($product)
    {
        // Se já temos featured_image
        if (isset($product['featured_image'])) {
            if (is_array($product['featured_image'])) {
                return $product['featured_image'];
            }
            return ['src' => $product['featured_image']];
        }
        
        // Tente obter do featured_media
        if (isset($product['featured_media']) && isset($product['featured_media']['preview_image'])) {
            return $product['featured_media']['preview_image'];
        }
        
        // Tente obter a primeira imagem
        $images = $this->processProductImages($product);
        return !empty($images) ? $images[0] : ['src' => '/img/no-image.jpg'];
    }

   /**
 * Processa variantes do produto com COMPLETA referência a mídia
 */
private function processProductVariants($product): array
{
    $variants = [];
    $mediaItems = $this->processProductMedia($product);
    
    if (isset($product['variants']) && is_array($product['variants'])) {
        $variants = $product['variants'];
        
        // Aprimoramento completo para cada variante
        foreach ($variants as &$variant) {
            // ID da variante é requerido
            if (!isset($variant['id'])) {
                $variant['id'] = uniqid('variant_');
            }
            
            // Garantir que a variante tenha um título
            if (!isset($variant['title'])) {
                $variant['title'] = 'Default';
            }
            
            // Garantir que disponibilidade seja definida
            if (!isset($variant['available'])) {
                $variant['available'] = true;
            }
            
            // Garantir que variante tenha preço
            if (!isset($variant['price'])) {
                $variant['price'] = $product['price'] ?? 10.0;
            }
            
            // Garantir always que tenha sku, mesmo vazio
            if (!isset($variant['sku'])) {
                $variant['sku'] = '';
            }
            
            // Se a variante não tem featured_media, mas tem image_id
            if (!isset($variant['featured_media']) && isset($variant['image_id'])) {
                // Procura a imagem correspondente na coleção de mídia
                foreach ($mediaItems as $mediaItem) {
                    if (isset($mediaItem['id']) && $mediaItem['id'] == $variant['image_id']) {
                        $variant['featured_media'] = $mediaItem;
                        break;
                    }
                }
            }
            
            // Se a variante tem imagem mas não tem featured_media
            if (!isset($variant['featured_media']) && isset($variant['image']) && is_array($variant['image'])) {
                // Convert variant image to full media object
                $variant['featured_media'] = $this->createMediaFromImage($variant['image'], 'variant_media_' . $variant['id']);
            }
            
            // Caso não encontre, pega a featured_media do produto
            if (!isset($variant['featured_media'])) {
                $variant['featured_media'] = $this->getProductFeaturedMedia($product);
            }
        }
    }
    
    return $variants;
}


/**
 * Cria um objeto de mídia a partir de uma imagem
 */
private function createMediaFromImage($image, $id = null): array
{
    // Determine content type based on file extension
    $contentType = 'image/jpg'; // Default
    if (isset($image['src'])) {
        $extension = pathinfo($image['src'], PATHINFO_EXTENSION);
        if (strtolower($extension) === 'png') {
            $contentType = 'image/png';
        } elseif (strtolower($extension) === 'gif') {
            $contentType = 'image/gif';
        }
    }
    
    // Calculate aspect ratio
    $width = $image['width'] ?? 1000;
    $height = $image['height'] ?? 1000;
    $aspectRatio = $width / $height;
    
    // Complete media object in Shopify format
    return [
        'id' => $id ?? ($image['id'] ?? uniqid('media_')),
        'position' => $image['position'] ?? 1,
        'media_type' => 'image', // IMPORTANT: Always set to 'image' for images
        'media_content_type' => $contentType,
        'preview_image' => array_merge($image, ['aspect_ratio' => $aspectRatio]),
        'alt' => $image['alt'] ?? '',
        'width' => $width,
        'height' => $height,
        'src' => $image['src'] ?? '',
        'aspect_ratio' => $aspectRatio,
    ];
}

   /**
 * Retorna a primeira variante disponível com estrutura completa
 */
private function getFirstAvailableVariant($product)
{
    if (isset($product['variants']) && is_array($product['variants'])) {
        // Primeiro tenta encontrar uma variante disponível
        foreach ($product['variants'] as $variant) {
            if (isset($variant['available']) && $variant['available']) {
                // Garante que a variante tenha featured_media
                if (!isset($variant['featured_media'])) {
                    $variant['featured_media'] = $this->getProductFeaturedMedia($product);
                }
                return $variant;
            }
        }
        
        // Se nenhuma estiver disponível, retorna a primeira
        if (!empty($product['variants'])) {
            $variant = $product['variants'][0];
            // Garante que a variante tenha featured_media
            if (!isset($variant['featured_media'])) {
                $variant['featured_media'] = $this->getProductFeaturedMedia($product);
            }
            return $variant;
        }
    }
    
    // Cria uma variante default completa
    return [
        'id' => $product['id'] ?? uniqid('var_'),
        'title' => 'Default',
        'price' => $product['price'] ?? '10.00',
        'available' => $product['available'] ?? true,
        'featured_media' => $this->getProductFeaturedMedia($product),
        'sku' => '',
        'inventory_quantity' => $product['inventory_quantity'] ?? 999,
        'requires_shipping' => true
    ];
}

    /**
     * Check if product has only default variant
     */
    private function hasOnlyDefaultVariant($variants): bool
    {
        if (!is_array($variants)) {
            return true;
        }
        
        // Se não há variantes ou apenas uma, tem apenas a default
        if (count($variants) <= 1) {
            return true;
        }
        
        // Se há múltiplas variantes, mas todas têm o título "Default"
        $allDefault = true;
        foreach ($variants as $variant) {
            if (isset($variant['title']) && $variant['title'] !== 'Default') {
                $allDefault = false;
                break;
            }
        }
        
        return $allDefault;
    }

    /**
     * Get variant-specific images
     */
    private function getVariantImages($product): array
    {
        $variantImages = [];
        $variants = $product['variants'] ?? [];
        
        // Adiciona imagens específicas de variantes
        foreach ($variants as $variant) {
            if (isset($variant['featured_media']) && isset($variant['featured_media']['preview_image']['src'])) {
                $variantImages[] = $variant['featured_media']['preview_image']['src'];
            } else if (isset($variant['image']) && isset($variant['image']['src'])) {
                $variantImages[] = $variant['image']['src'];
            }
        }
        
        // Remove duplicatas
        return array_unique($variantImages);
    }
    /**
     * Process nested JSON data to prevent double serialization
     */

    private function processNestedJsonData(&$data): void
    {
        if (!is_array($data)) {
            return;
        }
        
        foreach ($data as $key => &$value) {
            // If it's a string that looks like JSON
            if (is_string($value) && 
                (strpos($value, '{') === 0 || strpos($value, '[') === 0) && 
                json_decode($value) !== null && 
                json_last_error() === JSON_ERROR_NONE) {
                
                \Log::debug("Converting JSON string to object for key: {$key}");
                $value = json_decode($value, true);
            }
            
            // If it's an array, process recursively
            if (is_array($value)) {
                $this->processNestedJsonData($value);
            }
        }
    }



    /**
     * Ensure translations are loaded in the context
     */
    private function ensureTranslationsLoaded(Context $context): void
    {
        if (!isset($context->registers['translations']) || empty($context->registers['translations'])) {
            \Log::debug("Loading translations for template");
            
            // Get theme and locale
            $theme = [
                'store_id' => $context->get('theme.store_id'),
                'shopify_theme_id' => $context->get('theme.id')
            ];
            
            $locale = $context->get('locale') ?? request()->query('locale') ?? 'pt-BR';
            
            // Use ThemeTranslationManager to load translations
            $this->themeTranslationManager->initializeTranslations($context, (object)$theme, $locale);
        }
    }

    /**
     * Set additional product-related data in context for easy access
     */
    private function setAdditionalProductContext(Context $context, array $product): void
    {

        // Set product ID
        if (isset($product['id'])) {
            $context->set('product_id', $product['id']);
        }
        
        // Set media_count
        $mediaCount = isset($product['media']) && is_array($product['media']) ? count($product['media']) : 0;
        $context->set('media_count', $mediaCount);
        
        // Set first_3d_model to null if there are no 3D models
        // This prevents the template from trying to render the 3D viewer button
        $first3dModel = null;
        if (!empty($product['media'])) {
            foreach ($product['media'] as $media) {
                if (isset($media['media_type']) && $media['media_type'] === 'model') {
                    $first3dModel = $media;
                    break;
                }
            }
        }
        $context->set('first_3d_model', $first3dModel);
        
        // Ensure all media items have proper IDs
        if (!empty($product['media'])) {
            foreach ($product['media'] as &$mediaItem) {
                if (!isset($mediaItem['id']) || empty($mediaItem['id'])) {
                    $mediaItem['id'] = uniqid('media_');
                }
            }
        }

        // Set product ID
        if (isset($product['id'])) {
            $context->set('product_id', $product['id']);
        }
        
        // Set variants data
        if (!empty($product['variants'])) {
            $context->set('product_variants', $product['variants']);
            
            // Ensure selected variant is set
            $selectedVariant = $product['selected_or_first_available_variant'] ?? 
                $product['selected_variant'] ?? 
                (!empty($product['variants']) ? $product['variants'][0] : null);
                
            if ($selectedVariant) {
                $context->set('selected_variant', $selectedVariant);
                
                // Set inventory data
                if (isset($selectedVariant['inventory_quantity'])) {
                    $context->set('inventory_quantity', $selectedVariant['inventory_quantity']);
                }
                
                // Set SKU
                if (isset($selectedVariant['sku'])) {
                    $context->set('sku', $selectedVariant['sku']);
                }
            }
        }
        
        // Set media/images
        if (!empty($product['images'])) {
            $context->set('product_images', $product['images']);
            
            // Set featured image
            $featuredImage = $product['featured_image'] ?? 
                (!empty($product['images']) ? ($product['images'][0]['src'] ?? null) : null);
                
            if ($featuredImage) {
                $context->set('product_featured_image', $featuredImage);
            }
        }
        
        // Configurar imagens de variantes - usando o método especializado
        $context->set('variant_images', $this->getVariantImages($product));
        
        // Definir first_3d_model para o botão "Ver em 3D"
        $first3dModel = null;
        if (!empty($product['media'])) {
            foreach ($product['media'] as $media) {
                if (isset($media['media_type']) && $media['media_type'] === 'model') {
                    $first3dModel = $media;
                    break;
                }
            }
        }
        $context->set('first_3d_model', $first3dModel);
        
        // Define outros dados úteis para o template
        $context->set('media_count', $product['media_count'] ?? 0);
        
        // Define limite de mídia para paginação se necessário
        if (!empty($product['media']) && count($product['media']) > 10) {
            $context->set('media_pagination_limit', 10);
        }
        
        // Adiciona dados para o slider de miniaturas
        if (!empty($product['media']) && count($product['media']) > 1) {
            $context->set('enable_thumbnail_slider', true);
        }
        
        // Marca se o produto tem vídeos
        $hasVideo = false;
        if (!empty($product['media'])) {
            foreach ($product['media'] as $media) {
                if (isset($media['media_type']) && ($media['media_type'] === 'video' || $media['media_type'] === 'external_video')) {
                    $hasVideo = true;
                    break;
                }
            }
        }
        $context->set('product_has_video', $hasVideo);
        
        // Verifica se o produto tem zoom habilitado (para temas que suportam)
        $context->set('enable_image_zoom', true);
        
        // Define opções de tamanho para imagens responsivas
        $context->set('product_image_sizes', 
            "(min-width: 1200px) 600px, (min-width: 992px) 500px, (min-width: 768px) 350px, 100vw");
    }

    /**
     * Initialize theme settings in context
     */
    private function initializeThemeSettings(Context $context, Theme $theme, string $locale = null): void
    {
        try {
            // Use default locale if none specified
            $locale = $locale ?? $this->themeTranslationManager->getDefaultLocale($theme);
            
            // Get theme path
            $themePath = "{$theme->store_id}/{$theme->shopify_theme_id}";
            
            // Initialize context with theme settings
            $this->themeSettingsService->initializeContext($context, $themePath, $locale);
            
            // Also store settings in context.registers for tags that need access
            $settings = $context->get('settings');
            if (is_array($settings) && isset($settings['color_schemes'])) {
                $context->set('color_schemes', $settings['color_schemes']);
            }

            if (is_array($settings)) {
                $context->registers['settings'] = $settings;
                
                // Initialize font variations BEFORE rendering the template
                if (isset($settings['type_body_font'])) {
                    $this->createFontVariants($context, $settings);
                }
            } else {
                // Fallback to avoid errors if settings is not an array
                $context->registers['settings'] = [];
                
                // Register error for debug
                \Log::warning('Settings is not an array', [
                    'theme_id' => $theme->id,
                    'settings_type' => gettype($settings)
                ]);
            }
        } catch (\Exception $e) {
            // Log error but don't interrupt flow
            \Log::error('Error initializing theme settings', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Initialize with empty values to avoid breaking templates
            $context->set('settings', []);
            $context->registers['settings'] = [];
        }
    }

    /**
     * Create font variants
     */
    private function createFontVariants(Context $context, array $settings): void
    {
        // Body
        $bodyFont = $settings['type_body_font'] ?? null;
        if ($bodyFont) {
            if (is_string($bodyFont)) {
                $parts = explode('_', $bodyFont);
                $family = $parts[0];
                $weight = 400;
                
                if (isset($parts[1]) && substr($parts[1], 0, 1) === 'n') {
                    $weight = intval(substr($parts[1], 1)) * 100;
                }
                
                $bodyFont = [
                    'family' => $family,
                    'fallback_families' => 'sans-serif',
                    'weight' => $weight,
                    'style' => 'normal'
                ];
            }
            
            $weight = $bodyFont['weight'] ?? 400;
            $boldWeight = min(($weight + 300), 900);
            
            $context->set('body_font_bold', array_merge($bodyFont, ['weight' => $boldWeight]));
            $context->set('body_font_italic', array_merge($bodyFont, ['style' => 'italic']));
            $context->set('body_font_bold_italic', array_merge($bodyFont, ['weight' => $boldWeight, 'style' => 'italic']));
        }
    }

    /**
     * Render layout with page content
     */
    private function renderLayout(Theme $theme, string $pageContent, Context $context): string
    {
        // Get default layout or use a fallback
        $layout = $this->themeContentService->getThemeFile($theme, 'layout/theme.liquid') 
            ?? '<!doctype html><html><head>{{ content_for_header }}</head><body>{{ content_for_layout }}</body></html>';
        
        // Debug log
        \Log::debug("Rendering layout. PageContent is " . (empty($pageContent) ? "EMPTY" : "FILLED"));
        
        // IMPORTANT: Pre-process the pageContent to replace asset references
        $processedPageContent = $this->themeAssetManager->processAssetReferences($pageContent, [
            'store_id' => $context->get('theme.store_id'), 
            'theme_id' => $context->get('theme.id')
        ]);
        
        // Set content in context
        $context->set('content_for_layout', $processedPageContent);
        
        // Ensure content_for_header is set in context
        if (!$context->get('content_for_header')) {
            $context->set('content_for_header', $this->themeContentService->generateContentForHeader($theme, request()));
        }
        
        // Create a new template for the layout
        $layoutTemplate = clone $this->engine;
        
        // Register filters and tags
        $this->liquidTagsAndFiltersManager->registerFilters($layoutTemplate, $context);
        
        // Try to parse and render
        try {
            $layoutTemplate->parse($layout);
            $result = $layoutTemplate->render($context->getAll());
            
            // Check if the result contains literal content_for_layout (not substituted)
            if (strpos($result, '{{ content_for_layout }}') !== false) {
                \Log::error("Rendering problem: {{ content_for_layout }} was not substituted!");
                
                // Force manual substitution as fallback
                $result = str_replace('{{ content_for_layout }}', $processedPageContent, $result);
            }
            
            // Also check for unsubstituted content_for_header
            if (strpos($result, '{{ content_for_header }}') !== false) {
                \Log::error("Rendering problem: {{ content_for_header }} was not substituted!");
                
                // Force manual substitution as fallback
                $result = str_replace(
                    '{{ content_for_header }}', 
                    $context->get('content_for_header'), 
                    $result
                );
            }
            
            return $result;
        } catch (\Exception $e) {
            \Log::error("Error rendering layout: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            // Manual fallback if there's an error
            $header = $context->get('content_for_header') ?? '';
            $layout = str_replace('{{ content_for_header }}', $header, $layout);
            return str_replace('{{ content_for_layout }}', $processedPageContent, $layout);
        }
    }

    /**
     * Render JSON template processing its sections
     */
    private function renderJsonTemplate(array $templateData, Theme $theme, Context $context): string
    {
        $output = '';
        $sections = $templateData['sections'] ?? [];
        $order = $templateData['order'] ?? array_keys($sections);
        
        // Process sections in specified order
        foreach ($order as $sectionId) {
            if (!isset($sections[$sectionId])) {
                \Log::warning("Section {$sectionId} not found in template data");
                continue;
            }
            
            $sectionData = $sections[$sectionId];
            $sectionType = $sectionData['type'] ?? '';
            
            if (empty($sectionType)) {
                \Log::warning("Section {$sectionId} has no type");
                continue;
            }
            
            // Add section ID to data
            $sectionData['id'] = $sectionId;
            
            try {
                // Render section using the same method as ThemeController
                $sectionContent = $this->renderSection($sectionType, $sectionData, $theme, $context);
                
                $output .= $sectionContent;
            } catch (\Exception $e) {
                \Log::error("Error rendering section {$sectionType}: " . $e->getMessage());
                
                if (config('app.env') === 'local' || config('app.debug')) {
                    $output .= "<!-- Error rendering section {$sectionId} ({$sectionType}): " . 
                            htmlspecialchars($e->getMessage()) . " -->";
                }
            }
        }
        
        return $output;
    }

   /**
     * Render a specific section
     */
    private function renderSection(string $sectionType, array $sectionData, Theme $theme, Context $context): string
    {

        /*

        // Initial debugging
        \Log::debug("===== SECTION DEBUG START: {$sectionType} =====", [
            'section_id' => $sectionData['id'] ?? 'unknown',
            'theme_id' => $theme->shopify_theme_id ?? 'unknown',
            'theme_store_id' => $theme->store_id ?? 'unknown'
        ]);
        
        */

        /*
        // Debug section data keys and important values
        \Log::debug("Section data inspection:", [
            'section_keys' => array_keys($sectionData),
            'has_blocks' => isset($sectionData['blocks']),
            'blocks_count' => isset($sectionData['blocks']) ? (
                is_array($sectionData['blocks']) ? count($sectionData['blocks']) : 'blocks not array'
            ) : 'no blocks',
            'has_settings' => isset($sectionData['settings']),
            'settings_keys' => isset($sectionData['settings']) ? array_keys($sectionData['settings']) : 'no settings',
        ]);

        */
        
        // Look up section file
        $sectionPath = "sections/{$sectionType}.liquid";
        $sectionContent = $this->themeContentService->getThemeFile($theme, $sectionPath);
        
        // Debug section content
        \Log::debug("Section content loaded:", [
            'section_path' => $sectionPath,
            'content_found' => !empty($sectionContent),
            'content_length' => $sectionContent ? strlen($sectionContent) : 0,
            'content_sample' => $sectionContent ? substr($sectionContent, 0, 100) . '...' : 'EMPTY'
        ]);
        
        if (!$sectionContent) {
            // Section not found
            \Log::warning("Section file not found: {$sectionPath}");
            
            if (config('app.env') === 'development') {
                return "<!-- Section not found: {$sectionType} -->";
            }
            return '';
        }
                
        // Pre-processing
        $sectionContent = $this->themeTemplateRenderer->removeThemeCheckTags($sectionContent);
        
        $this->processNestedJsonData($sectionData);

        try {
            // Look up section file
            $sectionPath = "sections/{$sectionType}.liquid";
            $sectionContent = $this->themeContentService->getThemeFile($theme, $sectionPath);
            
            if (!$sectionContent) {
                \Log::warning("Section file not found: {$sectionPath}");
                return "<!-- Section not found: {$sectionType} -->";
            }
            
            // Pre-processing
            $sectionContent = $this->themeTemplateRenderer->removeThemeCheckTags($sectionContent);
            
            // Create new context for section
            $sectionContext = $this->themeContextManager->createSectionContext($context, $sectionData);
            
            // Debug context before blocks processing
            \Log::debug("Section context before blocks processing:", [
                'context_keys' => array_keys($sectionContext->getAll()),
                'has_section' => $sectionContext->hasKey('section'),
                'has_product' => $sectionContext->hasKey('product'),
                'product_id' => $sectionContext->hasKey('product') ? 
                    ($sectionContext->get('product.id') ?? 'no id') : 'no product',
                'shop_info' => $sectionContext->hasKey('shop') ? 
                    json_encode(array_keys($sectionContext->get('shop') ?? [])) : 'no shop'
            ]);
            
            // Process blocks for the section
            $this->processBlocksForSection($sectionData);
            
            /*
            // Debug section data after blocks processing
            \Log::debug("Section data after blocks processing:", [
                'blocks_count' => isset($sectionData['blocks']) ? 
                    (is_array($sectionData['blocks']) ? count($sectionData['blocks']) : 'blocks not array') : 'no blocks',
                'blocks_size' => isset($sectionData['blocks']['size']) ? $sectionData['blocks']['size'] : 'no size property',
                'has_first_block' => isset($sectionData['blocks']['first']),
                'section_data_keys' => array_keys($sectionData)
            ]);
            */

            // Set section in context
            $sectionContext->set('section', $sectionData);
            
            // Add global objects
            $request = request();
            $this->globalObjectsProvider->provide($sectionContext, [
                'request' => $request,
                'theme' => $theme,
                'template' => 'section',
                'section' => $sectionData,
                'product' => $context->get('product') // Ensure product is available in section
            ]);

            /*
            
            // Debug context after global objects
            \Log::debug("Section context after global objects:", [
                'context_keys' => array_keys($sectionContext->getAll()),
                'all_registers' => isset($sectionContext->registers) ? 
                    array_keys($sectionContext->registers) : 'no registers',
                'product_details' => $sectionContext->hasKey('product') ? [
                    'id' => $sectionContext->get('product.id') ?? 'no id',
                    'title' => $sectionContext->get('product.title') ?? 'no title',
                    'handle' => $sectionContext->get('product.handle') ?? 'no handle',
                    'has_variants' => $sectionContext->hasKey('product.variants') && 
                        is_array($sectionContext->get('product.variants')),
                    'variants_count' => $sectionContext->hasKey('product.variants') && 
                        is_array($sectionContext->get('product.variants')) ? 
                        count($sectionContext->get('product.variants')) : 0
                ] : 'product not available'
            ]);

            */
            
            // Create template instance
            $template = clone $this->engine;
            
            // Register filters
            $this->liquidTagsAndFiltersManager->registerFilters($template, $sectionContext);
            
            // Parse and render template
            $template->parse($sectionContent);
            $renderedContent = $template->render($sectionContext->getAll());
            
            // Debug rendered content
            \Log::debug("Section rendering results:", [
                'rendering_success' => !empty($renderedContent),
                'content_length' => strlen($renderedContent),
                'has_unprocessed_liquid' => (strpos($renderedContent, '{{') !== false || 
                    strpos($renderedContent, '{%') !== false),
                'sample' => substr($renderedContent, 0, 100) . '...'
            ]);
            
            // Process any remaining Liquid tags that weren't processed
            if (strpos($renderedContent, '{{') !== false || strpos($renderedContent, '{%') !== false) {
                $renderedContent = $this->themeTemplateRenderer->processUnprocessedLiquidTags($renderedContent, $sectionContext, $theme);
            }
            
            // Process asset references
            $renderedContent = $this->themeAssetManager->processAssetReferences($renderedContent, [
                'store_id' => $theme->store_id, 
                'theme_id' => $theme->shopify_theme_id
            ]);
            
            // Final wrapped result
            $finalResult = sprintf(
                '<div id="shopify-section-%s" class="shopify-section section section--%s" data-section-id="%s" data-section-type="%s">%s</div>',
                $sectionData['id'],
                $sectionType,
                $sectionData['id'],
                $sectionType,
                $renderedContent
            );
            
            \Log::debug("===== SECTION DEBUG END: {$sectionType} =====");
            
            return $finalResult;
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
     * Process blocks for section data
     */
    private function processBlocksForSection(array &$sectionData): void
    {
        if (isset($sectionData['blocks']) && isset($sectionData['block_order'])) {
            // Create blocks collection with both indexed and associative elements
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
                
                // Add all blocks as indexed elements for for-loop access
                for ($i = 0; $i < $size; $i++) {
                    $processedBlocks[$i] = $blocksByIndex[$i];
                }
            }
            
            // Update section data with processed blocks
            $sectionData['blocks'] = $processedBlocks;
        } else {
            // Initialize with empty blocks collection
            $sectionData['blocks'] = ['size' => 0];
        }
    }

    /**
     * Check if a string is valid JSON
     */
    private function isValidJson(string $string): bool
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    public function recommendation(Request $request)
    {
    
        return 200;
    }
}