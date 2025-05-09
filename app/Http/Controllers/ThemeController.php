<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Liquid\{Template, LocalFileSystem};
use App\Models\Theme;
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

class ThemeController extends Controller
{
    /*****************************************************************
     *                    CONSTANTS & PROPERTIES
     *****************************************************************/

    /**
     * Maximum rendering depth
     */
    const MAX_RENDER_DEPTH = 10;

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
        LiquidTagsAndFiltersManager $liquidTagsAndFiltersManager
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
     * Main endpoint for rendering the theme
     */
    public function renderTemplate(Request $request)
    {
        try {
            $path = $request->path();
            
            // Check if this is a product page that should be handled by ProductController
            if (Str::startsWith($path, 'produtos/') && !Str::is('produtos', $path)) {
                // Redirect to ProductController
                return app(ProductController::class)->renderProduct($request);
            }
            
            $shop = $this->themeContextManager->getShop($request);
            $theme = $this->themeContextManager->getTheme($shop);
            
            // Get template name
            $templateName = $this->themeContentService->resolveTemplateName($path);
            
            // Check if template exists for non-standard paths
            if (!in_array($templateName, ['index', 'cart', '404'])) {
                $jsonPath = "templates/{$templateName}.json";
                $liquidPath = "templates/{$templateName}.liquid";
                
                $jsonExists = Storage::disk('themes')->exists("{$theme->store_id}/{$theme->shopify_theme_id}/{$jsonPath}");
                $liquidExists = Storage::disk('themes')->exists("{$theme->store_id}/{$theme->shopify_theme_id}/{$liquidPath}");
                
                // If template doesn't exist, set to 404
                if (!$jsonExists && !$liquidExists) {
                    $templateName = '404';
                    $path = '404';
                }
            }
            
            // Build base context with all necessary data
            $baseContext = $this->themeContextManager->buildContext(
                $request, 
                $theme, 
                function($theme, $request) {
                    return $this->themeContentService->generateContentForHeader($theme, $request);
                }
            );
            $context = $this->themeContextManager->createContext($baseContext);
            
            // Initialize the Liquid engine
            $this->initLiquidEngine($theme);
            
            // Add global objects to context using the provider
            $this->themeContextManager->addGlobalObjects(
                $context, 
                $this->globalObjectsProvider, 
                $request, 
                $theme, 
                $templateName
            );
            
            // Initialize translations
            $this->themeTranslationManager->initializeTranslations($context, $theme, $request->query('locale'));
            
            // Initialize theme settings
            $this->initializeThemeSettings($context, $theme, $request->query('locale'));
            
            // Debug log
            \Log::debug("Rendering template for path: {$path}, template: {$templateName}");
            
            // Generate content based on path
            $pageContent = $this->themeContentService->generateContentForLayout(
                $path, 
                $theme, 
                $context,
                function($sectionType, $sectionData, $theme, $context) {
                    return $this->renderSection($sectionType, $sectionData, $theme, $context);
                }
            );
            \Log::debug("Page content generated: " . (empty($pageContent) ? "EMPTY" : "FILLED"));
            
            // Render layout with generated content
            $layoutContent = $this->renderLayout($theme, $pageContent, $context);
            
            // Finalize and return content
            $response = response($this->themeTemplateRenderer->finalizeOutput(
                $layoutContent, 
                $context->getAll(),
                $this->themeTranslationManager
            ))
                ->header('Content-Type', 'text/html; charset=UTF-8');
            
            // Set 404 status code if we're showing a 404 page
            if ($templateName === '404') {
                $response->setStatusCode(404);
            }
            
            return $response;
        } catch (\Throwable $e) {
            \Log::error("Error rendering template: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->themeErrorHandler->handleRenderError($e, $request);
        }
    }

    /**
     * Render a Liquid template with context
     */
    private function renderLiquidTemplate(string $content, Context $context, Theme $theme, string $path = ''): mixed
    {
        static $renderDepth = 0;
    
        $request = request();
        $templateName = $this->themeContentService->resolveTemplateName($path ?: $request->path());
        
        $renderDepth++;
        
        try {
            // Prevent excessive recursion
            if ($renderDepth > self::MAX_RENDER_DEPTH) {
                return response(
                    "<!-- Maximum template rendering depth (" . self::MAX_RENDER_DEPTH . ") exceeded. -->",
                    500
                )->header('Content-Type', 'text/html; charset=UTF-8');
            }
            
            // Pre-process to remove theme-check tags
            $content = $this->themeTemplateRenderer->removeThemeCheckTags($content);
            
            // Load Shopify context
            $this->shopifyContextFactory->loadContext($context, $theme, $request);
            
            // Add global objects to context using provider
            $this->globalObjectsProvider->provide($context, [
                'request' => $request,
                'theme' => $theme,
                'template' => $templateName
            ]);
            
            // Initialize translations in context
            $locale = $request->query('locale');
            $this->themeTranslationManager->initializeTranslations($context, $theme, $locale);
            
            // Initialize theme settings in context
            $this->initializeThemeSettings($context, $theme, $locale);
            
            try {
                // Render the template and layout
                $pageContent = $this->themeTemplateRenderer->renderTemplateContent($content, $context);
                $layoutContent = $this->renderLayout($theme, $pageContent, $context);
                
                return response($this->themeTemplateRenderer->finalizeOutput(
                    $layoutContent, 
                    $context->getAll(),
                    $this->themeTranslationManager
                ))->header('Content-Type', 'text/html; charset=UTF-8');
            } catch (\ErrorException $e) {
                throw $e;
            }
        } catch (\Throwable $e) {
            throw $e;
        } finally {
            $renderDepth--;
        }
    }

    /*****************************************************************
     *                       LAYOUT HANDLING
     *****************************************************************/

    /**
     * Render layout with page content
     */
    private function renderLayout(Theme $theme, string $pageContent, Context $context): string
    {
        // Get default layout or use fallback
        $layout = $this->themeContentService->getThemeFile($theme, 'layout/theme.liquid') 
            ?? '<!doctype html><html><head>{{ content_for_header }}</head><body>{{ content_for_layout }}</body></html>';
        
        // Debug log
        \Log::debug("Rendering layout. PageContent is " . (empty($pageContent) ? "EMPTY" : "FILLED"));
        
        // IMPORTANT: Pre-process pageContent to replace asset references
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
        
        // Create new template for layout
        $layoutTemplate = clone $this->engine;
        
        // Register filters and tags
        $this->liquidTagsAndFiltersManager->registerFilters($layoutTemplate, $context);
        
        // Try to parse and render
        try {
            $layoutTemplate->parse($layout);
            $result = $layoutTemplate->render($context->getAll());
            
            // Check if result contains literal content_for_layout (not replaced)
            if (strpos($result, '{{ content_for_layout }}') !== false) {
                \Log::error("Rendering problem: {{ content_for_layout }} was not replaced!");
                
                // Force manual replacement as fallback
                $result = str_replace('{{ content_for_layout }}', $processedPageContent, $result);
            }
            
            // Also check for unsubstituted content_for_header
            if (strpos($result, '{{ content_for_header }}') !== false) {
                \Log::error("Rendering problem: {{ content_for_header }} was not replaced!");
                
                // Force manual replacement as fallback
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

    /*****************************************************************
     *                      SECTION HANDLING
     *****************************************************************/

    /**
     * Render a specific section
     */
    private function renderSection(string $sectionType, array $sectionData, Theme $theme, Context $context): string
    {
        // Debug log
        \Log::debug("Starting section rendering: {$sectionType}", [
            'section_id' => $sectionData['id'] ?? 'unknown'
        ]);
        
        // Look up section file
        $sectionPath = "sections/{$sectionType}.liquid";
        $sectionContent = $this->themeContentService->getThemeFile($theme, $sectionPath);
        
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
        $sectionContent = $this->preprocessSectionContent($sectionContent, $sectionType, $theme);
        
        try {
            // Create new context for section
            $sectionContext = $this->themeContextManager->createSectionContext($context, $sectionData);
            
            // Process blocks and add to section data
            $this->processBlocksForSection($sectionData);
            
            // Handle special case for 'block' snippet
            if (!Storage::disk('themes')->exists("{$theme->store_id}/{$theme->shopify_theme_id}/snippets/block.liquid") 
                && strpos($sectionContent, "{% render 'block'") !== false) {
                
                // Create fallback implementation that processes block content directly
                $blockSnippetContent = '{{ block.shopify_attributes }}';
                
                // Temporarily add this snippet to rendered context
                $sectionContext->set('block_snippet_content', $blockSnippetContent);
                
                // Replace render calls with fallback content
                $sectionContent = preg_replace(
                    '/\{%\s*render\s+\'block\'.*?%\}/', 
                    '{{ block.shopify_attributes }}', 
                    $sectionContent
                );
            }
            
            // Set section in context
            $sectionContext->set('section', $sectionData);
            
            // Load schema and apply settings
            $this->themeSchemaManager->loadAndApplySchema(
                $sectionContext, 
                $theme, 
                $sectionType, 
                $sectionData, 
                function($theme, $path) {
                    return $this->themeContentService->getThemeFile($theme, $path);
                }
            );
            
            // Add global objects
            $request = request();
            $this->globalObjectsProvider->provide($sectionContext, [
                'request' => $request,
                'theme' => $theme,
                'template' => 'section',
                'section' => $sectionData
            ]);
            
            // Create template instance
            $template = clone $this->engine;
            
            // Register filters
            $this->liquidTagsAndFiltersManager->registerFilters($template, $sectionContext);
            
            // Register translation filter directly on this template instance
            $this->themeTranslationManager->registerTranslationFilter($template, $sectionContext);
            
            // Parse and render template
            $template->parse($sectionContent);
            $renderedContent = $template->render($sectionContext->getAll());
            
            // Post-processing
            if (strpos($renderedContent, '{{') !== false || strpos($renderedContent, '{%') !== false) {
                $renderedContent = $this->themeTemplateRenderer->processUnprocessedLiquidTags($renderedContent, $sectionContext, $theme);
            }
            
            $renderedContent = $this->themeAssetManager->processAssetReferences($renderedContent, [
                'store_id' => $theme->store_id, 
                'theme_id' => $theme->shopify_theme_id
            ]);
            
            // Wrap in section container
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
     * Modular function for preprocessing components with support for dynamic SVGs
     */
    private function preprocessSectionContent(string $sectionContent, string $sectionType, Theme $theme): string
    {
        // Debug log
        \Log::debug("Preprocessing section content: {$sectionType}");
        
        // 1. Replace references to CSS files
        $sectionContent = preg_replace_callback(
            '/\{\{\s*[\'"]?([^\'"}]*\.css)[\'"]?\s*\|\s*asset_url\s*\|\s*stylesheet_tag\s*\}\}/i',
            function($matches) use ($theme) {
                $cssFile = $matches[1];
                return '<link rel="stylesheet" href="' . url("assets/{$theme->store_id}/{$theme->shopify_theme_id}/{$cssFile}") . '" media="print" onload="this.media=\'all\'">';
            },
            $sectionContent
        );
        
        // 2. Replace references to placeholder_svg_tag using dynamic method
        $sectionContent = preg_replace_callback(
            '/\{\{\s*[\'"]?([^\'"}]*)[\'"]?\s*\|\s*placeholder_svg_tag(?:\s*:\s*[\'"]([^\'"]*)[\'"])?(?:\s*\}\})?/i',
            function($matches) use ($theme) {
                $svgName = trim($matches[1], "'\"");
                $class = isset($matches[2]) ? $matches[2] : '';
                
                return $this->themeAssetManager->generateSvgPlaceholder($svgName, $class, $theme);
            },
            $sectionContent
        );
        
        // 3. Replace references to inline_asset_content for SVGs
        $sectionContent = preg_replace_callback(
            '/\{\{-?\s*[\'"]?([^\'"}]*\.svg)[\'"]?\s*\|\s*inline_asset_content\s*-?\}\}/i',
            function($matches) use ($theme) {
                $svgFile = $matches[1];
                return $this->themeAssetManager->inlineSvgContent($svgFile, $theme);
            },
            $sectionContent
        );
        
        return $sectionContent;
    }
    
    /*****************************************************************
     *                   THEME SETTINGS
     *****************************************************************/

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
                
                // Initialize font variations BEFORE rendering template
                if (isset($settings['type_body_font'])) {
                    $this->createFontVariants($context, $settings);
                }
            } else {
                // Fallback to avoid errors if settings is not an array
                $context->registers['settings'] = [];
                
                // Log error for debug
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
     * Create font variants in context
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
        
        // Debug
        \Log::debug("createFontVariants settings:", [
            'has_color_schemes' => isset($settings['color_schemes']),
            'settings_keys' => array_keys($settings),
            'color_schemes_value' => $settings['color_schemes'] ?? null
        ]);       
    }
}