<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Models\Theme;
use Liquid\Context;
use Illuminate\Support\Facades\Storage;
use App\Services\ThemeTemplateRenderer;
use App\Services\ThemeAssetManager;
use App\Services\TranslationService;

class ThemeContentService
{
    /**
     * @var ThemeTemplateRenderer
     */
    protected $themeTemplateRenderer;

    /**
     * @var ThemeAssetManager
     */
    protected $themeAssetManager;

    /**
     * @var TranslationService
     */
    protected $translationService;

    /**
     * Constructor
     */
    public function __construct(
        ThemeTemplateRenderer $themeTemplateRenderer,
        ThemeAssetManager $themeAssetManager,
        TranslationService $translationService
    ) {
        $this->themeTemplateRenderer = $themeTemplateRenderer;
        $this->themeAssetManager = $themeAssetManager;
        $this->translationService = $translationService;
    }

    /**
     * Generate the content_for_header data
     */
    public function generateContentForHeader(Theme $theme, Request $request): string
    {
        $output = '';
        
        // Add meta charset
        $output .= '<meta charset="utf-8">' . PHP_EOL;
        
        // Add viewport meta
        $output .= '<meta name="viewport" content="width=device-width, initial-scale=1.0, height=device-height, minimum-scale=1.0, maximum-scale=1.0">' . PHP_EOL;
        
        // Add theme info
        $output .= '<meta name="theme-id" content="' . $theme->shopify_theme_id . '">' . PHP_EOL;
        $output .= '<meta name="theme-name" content="' . htmlspecialchars($theme->name) . '">' . PHP_EOL;
        
        // Add CSRF token for AJAX requests
        $output .= '<meta name="csrf-token" content="' . csrf_token() . '">' . PHP_EOL;
        
        // Theme assets base URL
        $baseAssetUrl = url("assets/{$theme->store_id}/{$theme->shopify_theme_id}");
        $output .= '<meta name="asset-url" content="' . $baseAssetUrl . '">' . PHP_EOL;
        
        // Add global script with Shopify information
        $output .= '<script>' . PHP_EOL;
        $output .= 'var Shopify = Shopify || {};' . PHP_EOL;
        $output .= 'Shopify.shop = "' . ($theme->store->domain ?? 'example.com') . '";' . PHP_EOL;
        $output .= 'Shopify.locale = "' . ($request->query('locale') ?? 'pt-BR') . '";' . PHP_EOL;
        $output .= 'Shopify.currency = {"active":"BRL","rate":"1.0"};' . PHP_EOL;
        $output .= 'Shopify.country = "BR";' . PHP_EOL;
        $output .= 'Shopify.theme = {' . 
            '"name":"' . htmlspecialchars($theme->name) . '",' . 
            '"id":' . $theme->shopify_theme_id . ',' . 
            '"schema_name":"' . htmlspecialchars($theme->name) . '",' .
            '"schema_version":"' . ($theme->settings['theme_version'] ?? '15.3.0') . '",' .
            '"theme_store_id":' . ($theme->theme_store_id ?? '887') . ',' .
            '"role":"' . $theme->role . '"' .
        '};' . PHP_EOL;
        $output .= 'Shopify.theme.handle = "null";' . PHP_EOL;
        $output .= 'Shopify.theme.style = {"id":null,"handle":null};' . PHP_EOL;
        $output .= 'Shopify.cdnHost = "' . ($theme->store->domain ?? 'example.com') . '/cdn";' . PHP_EOL;
        $output .= 'Shopify.routes = Shopify.routes || {};' . PHP_EOL;
        $output .= 'Shopify.routes.root = "/";' . PHP_EOL;

        // Add store and theme specific data to window object for backward compatibility
        $output .= 'window.store = ' . json_encode([
            'id' => $theme->store_id,
            'name' => $theme->store->name ?? 'Store',
            'domain' => request()->getHost() ?? 'example.com',
            'currency' => 'BRL',
            'money_format' => 'R$ {{amount}}',
        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) . ';' . PHP_EOL;

        // Add theme settings
        if (isset($theme->settings) && is_array($theme->settings)) {
            // Remove potentially problematic values
            $safeSettings = array_filter($theme->settings, function($value) {
                return !is_resource($value) && !is_object($value);
            });
            
            $output .= 'window.theme = ' . json_encode($safeSettings, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) . ';' . PHP_EOL;
        } else {
            $output .= 'window.theme = {};' . PHP_EOL;
        }

        // Store the current path (not directly setting location.pathname to avoid infinite reload)
        $safePath = addslashes($request->path());
        $output .= 'window.themeCurrentPath = "' . $safePath . '";' . PHP_EOL;
        $output .= '</script>' . PHP_EOL;
        
        // Include all core scripts from the screenshot
        // Order matters - load critical scripts like preloads.js first
        $output .= '<!-- Core scripts -->' . PHP_EOL;

        /*
        $output .= '<script src="' . url("storage/scripts/preloads.js") . '"></script>' . PHP_EOL;
        $output .= '<script src="' . url("storage/scripts/shopify-perf-kit-1.6.1.min.js") . '" defer></script>' . PHP_EOL;
        $output .= '<script src="' . url("storage/scripts/load_feature.js") . '" defer></script>' . PHP_EOL;
        $output .= '<script src="' . url("storage/scripts/browser.js") . '" defer></script>' . PHP_EOL;
        $output .= '<script src="' . url("storage/scripts/scripts.js") . '" defer></script>' . PHP_EOL;
        */
        
        $output .= '<!-- Core Styles -->' . PHP_EOL;

        $output .= '<link rel="stylesheet" href="' . url("storage/styles/app.css") . '">' . PHP_EOL;
        $output .= '<link rel="stylesheet" href="' . url("storage/styles/DeliveryMethodSelectorSection.css") . '">' . PHP_EOL;
        $output .= '<link rel="stylesheet" href="' . url("storage/styles/OnePage.css") . '">' . PHP_EOL;
        $output .= '<link rel="stylesheet" href="' . url("storage/styles/ShopPayVerificationSwitch.css") . '">' . PHP_EOL;
        $output .= '<link rel="stylesheet" href="' . url("storage/styles/StackedMerchandisePreview.css") . '">' . PHP_EOL;
        $output .= '<link rel="stylesheet" href="' . url("storage/styles/useEditorShopPayNavigation.css") . '">' . PHP_EOL;
        $output .= '<link rel="stylesheet" href="' . url("storage/styles/VaultedPayment.css") . '">' . PHP_EOL;

        return $output;
    }

    /**
     * Resolve the template name from a path
     */
    public function resolveTemplateName(string $path): string
    {
        // Special page mappings
        $mappings = [
            '/' => 'index', 
            'carrinho' => 'cart',
            'busca' => 'search',
            '404' => '404'
        ];
        
        $path = trim($path, '/');
        
        foreach ($mappings as $pattern => $name) {
            if (\Illuminate\Support\Str::is($pattern, $path)) {
                return $name;
            }
        }
        
        // For all other paths, return the path itself as the template name
        // This allows the system to look for custom templates
        return $path ?: 'index';
    }

    /**
     * Get theme file content with pre-processing
     */
    public function getThemeFile(Theme $theme, string $path): ?string
    {
        $fullPath = "{$theme->store_id}/{$theme->shopify_theme_id}/{$path}";
        
        if (Storage::disk('themes')->exists($fullPath)) {
            $content = Storage::disk('themes')->get($fullPath);
            
            // Pre-process to remove problematic tags
            if ($content) {
                $content = $this->themeTemplateRenderer->removeThemeCheckTags($content);
            }
            
            return $content;
        }
        
        return null;
    }

    /**
     * Generate content for layout based on the current path
     */
    public function generateContentForLayout(string $path, Theme $theme, Context $context, callable $sectionRenderer): string
    {
        // Determine template name from path
        $templateName = $this->resolveTemplateName($path);
        \Log::debug("Resolving content_for_layout for path: {$path}, templateName: {$templateName}");
        
        // DEBUG: Log translations in context
        \Log::debug("Translations in context before rendering", [
            'has_translations' => isset($context->registers['translations']),
            'translation_count' => isset($context->registers['translations']) ? count($context->registers['translations']) : 0,
            'translation_keys' => isset($context->registers['translations']) ? array_keys($context->registers['translations']) : [],
            'current_locale' => $context->registers['current_locale'] ?? 'not defined'
        ]);
        
        // DEBUG: Log all available context variables
        \Log::debug("Available context variables", [
            'keys' => array_keys($context->getAll()),
            'has_settings' => $context->get('settings') !== null,
            'has_cart' => $context->get('cart') !== null,
            'has_shop' => $context->get('shop') !== null
        ]);
        
        // Try to load JSON template first
        $jsonPath = "templates/{$templateName}.json";
        $jsonContent = $this->getThemeFile($theme, $jsonPath);
        
        // Process JSON template if it exists
        if ($jsonContent && $this->themeTemplateRenderer->isValidJson($jsonContent)) {
            \Log::debug("JSON template found and valid: {$jsonPath}");
            $templateData = json_decode($jsonContent, true);
            
            if (isset($templateData['sections']) && isset($templateData['order'])) {
                \Log::debug("Rendering JSON template with " . count($templateData['sections']) . " sections");
                
                // DEBUG: Log sections in JSON template
                \Log::debug("Sections in JSON template", [
                    'section_ids' => $templateData['order'],
                    'section_types' => array_map(function($id) use ($templateData) {
                        return $templateData['sections'][$id]['type'] ?? 'unknown';
                    }, $templateData['order'])
                ]);
                
                // Special handling for cart template
                if ($templateName === 'cart') {
                    \Log::debug("Processing cart template - adding cart context data");
                }
                
                $renderedContent = $this->themeTemplateRenderer->renderJsonTemplate(
                    $templateData, 
                    $theme, 
                    $context,
                    $sectionRenderer
                );
                
                // DEBUG: Check if content was rendered
                \Log::debug("JSON template rendered content", [
                    'content_length' => strlen($renderedContent),
                    'has_content' => !empty($renderedContent),
                    'sample' => substr($renderedContent, 0, 100) . '...'
                ]);
                
                // Process asset references
                $renderedContent = $this->themeAssetManager->processAssetReferences($renderedContent, [
                    'store_id' => $theme->store_id, 
                    'theme_id' => $theme->shopify_theme_id
                ]);
                
                return $renderedContent;
            }
        } else {
            \Log::debug("JSON template not found or invalid. Trying Liquid template.");
        }
        
        // Fallback to Liquid template
        $liquidPath = "templates/{$templateName}.liquid";
        $liquidContent = $this->getThemeFile($theme, $liquidPath);
        
        \Log::debug("Variables available in context before rendering Liquid:", [
            'color_schemes' => $context->get('color_schemes') ? 'present' : 'missing',
            'settings' => $context->get('settings') ? array_keys($context->get('settings')) : 'missing',
            'scheme_classes' => $context->get('scheme_classes') ? 'present' : 'missing',
            't_filter_registered' => isset($context->registers['filters']['t']),
            'environment' => [
                'template' => $templateName,
                'request_path' => $path
            ]
        ]);
    
        if ($liquidContent) {
            \Log::debug("Liquid template found: {$liquidPath}");
            
            // Special handling for cart template
            if ($templateName === 'cart') {
                \Log::debug("Processing cart template - adding cart context data");
                
                // DEBUG: Check cart data after preparation
                \Log::debug("Cart data after preparation", [
                    'cart' => $context->get('cart')
                ]);
            }
            
            // Ensure translations are loaded
            if (!isset($context->registers['translations']) || empty($context->registers['translations'])) {
                \Log::debug("Loading translations for template");
                $locale = request()->query('locale') ?? 'pt-BR';
                $themePath = "{$theme->store_id}/{$theme->shopify_theme_id}";
                
                $translations = $this->translationService->loadTranslationsForLocale($themePath, $locale);
                $context->registers['translations'] = $translations;
                $context->registers['current_locale'] = $locale;
                
                \Log::debug("Translations loaded", [
                    'locale' => $locale,
                    'translation_count' => count($translations),
                    'keys' => array_slice(array_keys($translations), 0, 10) // Show first 10 keys
                ]);
            }
            
            $renderedContent = $this->themeTemplateRenderer->renderTemplateContent($liquidContent, $context);
            
            // DEBUG: Check rendered content for translation keys
            if (preg_match_all('/(\w+\.\w+\.\w+)/', $renderedContent, $matches)) {
                \Log::debug("Potential untranslated keys in output", [
                    'keys' => array_unique($matches[0]),
                    'sample_content' => substr($renderedContent, 0, 200)
                ]);
            }
            
            // Process asset references
            $renderedContent = $this->themeAssetManager->processAssetReferences($renderedContent, [
                'store_id' => $theme->store_id, 
                'theme_id' => $theme->shopify_theme_id
            ]);
            
            return $renderedContent;
        } else {
            \Log::debug("Liquid template not found: {$liquidPath}");
        }
        
        // Check main index.liquid template as last resort
        if ($templateName !== 'index') {
            $indexPath = "templates/index.liquid";
            $indexContent = $this->getThemeFile($theme, $indexPath);
            
            if ($indexContent) {
                \Log::debug("Using index.liquid template as fallback");
                $renderedContent = $this->themeTemplateRenderer->renderTemplateContent($indexContent, $context);
                
                // Process asset references
                $renderedContent = $this->themeAssetManager->processAssetReferences($renderedContent, [
                    'store_id' => $theme->store_id, 
                    'theme_id' => $theme->shopify_theme_id
                ]);
                
                return $renderedContent;
            }
        }
        
        // Final fallback
        \Log::warning("No template found for: {$templateName}. Returning default page.");
        
        if (config('app.env') === 'local') {
            return "<div style='padding: 20px; text-align: center;'>
                    <h1>Template not found</h1>
                    <p>Could not find a template for: {$templateName}</p>
                    <p>Path: {$path}</p>
                </div>";
        }
        
        return "<div style='padding: 20px; text-align: center;'>
                <h1>Page under construction</h1>
                <p>New content will be available here soon.</p>
                </div>";
    }
}