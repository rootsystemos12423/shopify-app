<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Liquid\Template;
use Liquid\Context;
use App\Models\Theme;
use App\Services\ThemeAssetManager;
use App\Services\ThemeTranslationManager;

class ThemeTemplateRenderer
{
    /**
     * @var ThemeAssetManager
     */
    protected $themeAssetManager;

    /**
     * @var ThemeTranslationManager
     */
    protected $themeTranslationManager;

    /**
     * @var array Stores placeholders for critical blocks (style, script, etc.)
     */
    private array $placeholders = [];

    /**
     * Maximum content size
     */
    const MAX_CONTENT_SIZE = 2000000; // 2MB

    /**
     * Constructor
     */
    public function __construct(
        ThemeAssetManager $themeAssetManager,
        ThemeTranslationManager $themeTranslationManager
    ) {
        $this->themeAssetManager = $themeAssetManager;
        $this->themeTranslationManager = $themeTranslationManager;
    }

    /**
     * Renders the content of a template
     */
    public function renderTemplateContent(string $content, Context $context, Template $engine): string
    {
        // Remove theme-check tags
        $content = $this->removeThemeCheckTags($content);
        
        // Log for debug
        \Log::debug("Rendering template with " . strlen($content) . " chars");
        
        // Create a new template instance to avoid conflicts
        $template = clone $engine;
        
        // Register translation filter
        $this->themeTranslationManager->registerTranslationFilter($template, $context);
        
        try {
            // Check if content is too large
            if (strlen($content) > self::MAX_CONTENT_SIZE) {
                $content = substr($content, 0, self::MAX_CONTENT_SIZE) . 
                    "\n<!-- Content truncated due to size limitations -->";
            }
            
            // Parse and render
            $template->parse($content);
            $result = $template->render($context->getAll());
            
            // Check if the template was processed successfully
            if (strpos($result, '{{ ') !== false || strpos($result, '{%') !== false) {
                \Log::warning("Possible problem in template rendering - Liquid tags still present in result", [
                    'tags' => $this->extractLiquidTags($result)
                ]);
                
                // Try to process simple tags that weren't processed
                $result = $this->processUnprocessedTags($result, $context);
            }
            
            // Additional pass for translation keys that might have been missed
            if (preg_match_all('/(\w+\.\w+\.\w+)/', $result, $matches)) {
                $possibleTranslationKeys = array_unique($matches[0]);
                \Log::debug("Checking for missed translation keys", [
                    'keys' => $possibleTranslationKeys
                ]);
                
                foreach ($possibleTranslationKeys as $key) {
                    // Try to translate these possible keys
                    $translated = $this->themeTranslationManager->translateKey($key, $context);
                    if ($translated !== $key) {
                        $result = str_replace($key, $translated, $result);
                    }
                }
            }
            
            return $result;
        } catch (\Exception $e) {
            \Log::error("Error rendering template: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            if (config('app.env') === 'local' || config('app.debug')) {
                return "<!-- Error in template rendering: " . htmlspecialchars($e->getMessage()) . " -->";
            }
            
            return '';
        }
    }

    /**
     * Pre-processes content to remove problematic tags
     */
    public function preprocessContent(string $content): string
    {
        // Limit content size
        if (strlen($content) > self::MAX_CONTENT_SIZE) { 
            $content = substr($content, 0, self::MAX_CONTENT_SIZE) . 
                "\n<!-- Content truncated due to size limitations -->";
        }
        
        // Remove theme-check tags
        $content = $this->removeThemeCheckTags($content);
        
        // Normalize Liquid syntax
        $content = preg_replace('/\{%-\s*/', '{%', $content);
        $content = preg_replace('/\s*-\%\}/', '%}', $content);
        $content = preg_replace('/\{\{-\s*/', '{{', $content);
        $content = preg_replace('/\s*-\}\}/', '}}', $content);
        
        return $content;
    }

    /**
     * Remove theme-check tags from content
     */
    public function removeThemeCheckTags(string $content): string
    {
        // Remove theme-check tags
        $content = preg_replace('/{%-?\s*#\s*theme-check-(enable|disable).*?-?%}/s', '', $content);
        
        // Normalize Liquid syntax - THIS IS CRUCIAL
        $content = preg_replace('/\{%-\s*/', '{%', $content);
        $content = preg_replace('/\s*-\%\}/', '%}', $content);
        $content = preg_replace('/\{\{-\s*/', '{{', $content);
        $content = preg_replace('/\s*-\}\}/', '}}', $content);
        
        return $content;
    }

    /**
     * Helper method to extract unprocessed Liquid tags
     */
    public function extractLiquidTags(string $content): array
    {
        $tags = [];
        if (preg_match_all('/\{\{.*?\}\}|\{%.*?%\}/', $content, $matches)) {
            $tags = $matches[0];
        }
        return $tags;
    }

    /**
     * Process tags that weren't processed by Liquid
     */
    public function processUnprocessedTags(string $content, Context $context): string
    {
        // Process simple tags {{ x }}
        $content = preg_replace_callback('/\{\{\s*([a-zA-Z0-9_\.]+)\s*\}\}/', function($matches) use ($context) {
            $variable = $matches[1];
            $value = $context->get($variable);
            
            if (is_string($value) || is_numeric($value)) {
                return (string)$value;
            }
            
            return $matches[0]; // Keep if can't process
        }, $content);
        
        return $content;
    }

    /**
     * Preserve critical blocks during processing
     */
    public function preserveCriticalBlocks(string $content): string
    {
        return preg_replace_callback(
            '/<(style|script|textarea)[^>]*>.*?<\/\1>/is',
            fn($m) => $this->createPlaceholder($m[0]),
            $content
        );
    }

    /**
     * Create a placeholder for critical block
     */
    public function createPlaceholder(string $content): string
    {
        $key = '___BLOCK_'.md5($content).'___';
        $this->placeholders[$key] = $content;
        return $key;
    }

    /**
     * Restore preserved blocks
     */
    public function restorePreservedBlocks(string $content): string
    {
        return str_replace(
            array_keys($this->placeholders),
            array_values($this->placeholders),
            $content
        );
    }

    /**
     * Process Liquid tags not processed after main rendering
     */
    public function processUnprocessedLiquidTags(string $content, Context $context, Theme $theme): string
    {
        // Direct replacement for CSS asset links
        $content = preg_replace_callback(
            '/\{\{\s*[\'"]?([^\'"}]*\.css)[\'"]?\s*\|\s*asset_url\s*\|\s*stylesheet_tag\s*\}\}/i',
            function($matches) use ($theme) {
                $asset = $matches[1];
                $assetUrl = url("assets/{$theme->store_id}/{$theme->shopify_theme_id}/{$asset}");
                return "<link rel=\"stylesheet\" href=\"{$assetUrl}\" media=\"print\" onload=\"this.media='all'\">";
            },
            $content
        );
        
        // Replacement for hero-apparel placeholder
        $content = preg_replace_callback(
            '/\{\{\s*[\'"]?(hero-apparel-[12])[\'"]?\s*\|\s*placeholder_svg_tag(?:\s*:\s*[\'"]([^\'"]*)[\'"])?(?:\s*\}\})?/i',
            function($matches) {
                $type = $matches[1];
                $class = isset($matches[2]) ? $matches[2] : '';
                
                $placeholders = [
                    'hero-apparel-1' => '<svg class="placeholder-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 525 525"><rect width="525" height="525" fill="#F6F6F6"/><path d="M375,375H150V150H375Z" fill="#D8D8D8"/><path d="M262.5,336.6V188.4a37.4,37.4,0,0,1,37.4-37.4h0A37.4,37.4,0,0,1,337.3,188.4V336.6" fill="none" stroke="#A4A4A4" stroke-miterlimit="10" stroke-width="4"/><path d="M262.5,336.6V188.4a37.4,37.4,0,0,0-37.4-37.4h0a37.4,37.4,0,0,0-37.4,37.4V336.6" fill="none" stroke="#A4A4A4" stroke-miterlimit="10" stroke-width="4"/></svg>',
                    'hero-apparel-2' => '<svg class="placeholder-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 525 525"><rect width="525" height="525" fill="#F6F6F6"/><path d="M375,375H150V150H375Z" fill="#D8D8D8"/><path d="M262.5,337.5a75,75,0,0,1,0-150h0a75,75,0,0,1,0,150Z" fill="none" stroke="#A4A4A4" stroke-miterlimit="10" stroke-width="4"/></svg>'
                ];
                
                $svg = $placeholders[$type] ?? "<svg class=\"placeholder-svg\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 525 525\"><rect width=\"525\" height=\"525\" fill=\"#F6F6F6\"/><text x=\"50%\" y=\"50%\" text-anchor=\"middle\" dy=\"0.3em\" fill=\"#A4A4A4\">Placeholder</text></svg>";
                
                if (!empty($class)) {
                    // Add extra class if provided
                    $svg = str_replace('class="placeholder-svg"', 'class="placeholder-svg ' . $class . '"', $svg);
                }
                
                return $svg;
            },
            $content
        );
        
        // Replacement for inline SVG
        $content = preg_replace_callback(
            '/\{\{-?\s*[\'"]?([^\'"}]*\.svg)[\'"]?\s*\|\s*inline_asset_content\s*-?\}\}/i',
            function($matches) use ($theme) {
                $asset = $matches[1];
                $assetPath = "{$theme->store_id}/{$theme->shopify_theme_id}/assets/{$asset}";
                
                try {
                    if (\Storage::disk('themes')->exists($assetPath)) {
                        return \Storage::disk('themes')->get($assetPath);
                    }
                } catch (\Exception $e) {
                    \Log::warning("Couldn't load inline asset: {$assetPath}", [
                        'error' => $e->getMessage()
                    ]);
                }
                
                // If couldn't load content, use image as fallback
                $assetUrl = url("assets/{$theme->store_id}/{$theme->shopify_theme_id}/{$asset}");
                return "<img src=\"{$assetUrl}\" alt=\"\">";
            },
            $content
        );
        
        // Process simple variables
        $content = preg_replace_callback(
            '/\{\{\s*([a-zA-Z0-9_\.]+)\s*\}\}/',
            function($matches) use ($context) {
                $variable = $matches[1];
                $value = $context->get($variable);
                
                if (is_string($value) || is_numeric($value)) {
                    return (string)$value;
                } elseif (is_bool($value)) {
                    return $value ? 'true' : 'false';
                } elseif (is_null($value)) {
                    return '';
                }
                
                return $matches[0]; // Keep as is if can't convert
            },
            $content
        );
        
        return $content;
    }

    /**
     * Renders a JSON template by processing its sections
     */
    public function renderJsonTemplate(array $templateData, Theme $theme, Context $context, $sectionRenderer): string
    {
        $output = '';
        $sections = $templateData['sections'] ?? [];
        $order = $templateData['order'] ?? array_keys($sections);
        
        // Check if we have translations in original context
        $hasTranslations = isset($context->registers['translations']) && !empty($context->registers['translations']);
        
        if ($hasTranslations) {
            \Log::debug("Translations found in original context before rendering JSON template", [
                'translation_count' => count($context->registers['translations']),
                'locale' => $context->registers['current_locale'] ?? 'not defined'
            ]);
        } else {
            \Log::warning("Translations NOT found in original context before rendering JSON template");
        }
        
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
                // CRUCIAL: Pass the full original context to the section
                $sectionContent = $sectionRenderer($sectionType, $sectionData, $theme, $context);
                
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
     * Checks if a string is valid JSON
     */
    public function isValidJson(string $string): bool
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Checks if a template is of JSON type
     */
    public function isJsonTemplate(string $content): bool
    {
        return str_starts_with(trim($content), '{') && 
            json_decode($content) !== null;
    }

    /**
     * Finalizes and adjusts output before sending
     */
    public function finalizeOutput(string $content, array $contextData, ThemeTranslationManager $translationManager): string {
        // Get theme info for asset URLs
        $theme = $contextData['theme'];
        
        // Normalize theme identifiers
        $theme['id'] = $theme['id'] ?? $theme['theme_id'] ?? null;
        $theme['theme_id'] = $theme['theme_id'] ?? $theme['id'] ?? null;
        
        // Process and fix CSS issues with the improved functions
        $content = $this->themeAssetManager->processAssetReferences($content, $theme);

        // Process final translations if context has translations
        if (isset($contextData['registers']) && isset($contextData['registers']['translations'])) {
            $context = new Context();
            $context->registers['translations'] = $contextData['registers']['translations'];
            $context->registers['current_locale'] = $contextData['registers']['current_locale'] ?? 'pt-BR';
            
            $content = $translationManager->finalizeTranslations($content, $context);
        }
        
        // Add schema if it exists
        $schema = !empty($contextData['section_schema'])
            ? '<script>window.themeSchema='.json_encode($contextData['section_schema']).';</script>'
            : '';
    
        return $schema . $content;
    }
}