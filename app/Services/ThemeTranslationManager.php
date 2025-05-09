<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\Theme;
use Liquid\Context;

class ThemeTranslationManager
{
    /**
     * @var TranslationService
     */
    protected $translationService;

    /**
     * Constructor
     */
    public function __construct(TranslationService $translationService)
    {
        $this->translationService = $translationService;
    }

    /**
     * Initialize translations in the Liquid context
     */
    public function initializeTranslations(Context $context, Theme $theme, string $locale = null): void
    {
        try {
            // Define default locale if none specified
            $locale = $locale ?? $this->getDefaultLocale($theme);
            
            // Theme path
            $themePath = "{$theme->store_id}/{$theme->shopify_theme_id}";
            
            // Load translations from correct file
            $translations = $this->loadTranslationsDirectly($themePath, $locale);
            
            // Set in context
            $context->registers['translations'] = $translations;
            $context->registers['current_locale'] = $locale;
            
            // Also set locale as accessible variable
            $context->set('locale', $locale);
        } catch (\Exception $e) {
            Log::error('Error initializing translations', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Initialize with empty array to prevent template breaking
            $context->registers['translations'] = [];
            $context->registers['current_locale'] = $locale;
        }
    }

    /**
     * Load translations directly from file system
     */
    public function loadTranslationsDirectly(string $themePath, string $locale): array
    {
        $localeFile = "{$themePath}/locales/{$locale}.json";
        
        if (!Storage::disk('themes')->exists($localeFile)) {
            Log::warning("Translation file not found: {$localeFile}");
            return [];
        }
        
        try {
            $content = Storage::disk('themes')->get($localeFile);
            $translations = json_decode($content, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error("Error decoding translation JSON: " . json_last_error_msg());
                return [];
            }
            
            if (!is_array($translations)) {
                Log::error("Translations did not decode to an array");
                return [];
            }
            
            return $translations;
        } catch (\Exception $e) {
            Log::error("Exception loading translations: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get default locale for the theme
     */
    public function getDefaultLocale(Theme $theme): string
    {
        // Try to get locale from request
        $locale = request()->query('locale');
        
        // If no locale in the request, try to find the best based on browser
        if (!$locale) {
            $themePath = "{$theme->store_id}/{$theme->shopify_theme_id}";
            $availableLocales = $this->translationService->getAvailableLocales($themePath);
            
            if (!empty($availableLocales)) {
                // Use locale detector to choose the best
                $locale = $this->translationService->getBestLocale($availableLocales);
            } else {
                // Fallback to default
                $locale = config('app.locale', 'pt-BR');
            }
        }
        
        return $locale;
    }

    /**
     * Finalize translations in content
     */
    public function finalizeTranslations(string $content, Context $context): string
    {
        // Process any remaining translations in the final content
        // This captures translations that may have been inserted directly in the template
        // instead of in schema tags
        
        return preg_replace_callback('/t:([a-zA-Z0-9_\.-]+)/', function($matches) use ($context) {
            $key = $matches[1];
            return $this->translationService->translate('t:' . $key, [], $context);
        }, $content);
    }

    /**
     * Helper method to translate a key using the context's translations
     */
    public function translateKey(string $key, Context $context): string
    {
        if (!isset($context->registers['translations']) || empty($context->registers['translations'])) {
            return $key;
        }
        
        $translations = $context->registers['translations'];
        $parts = explode('.', $key);
        
        // Navigate the translations array
        $value = $translations;
        $allPartsFound = true;
        
        foreach ($parts as $part) {
            if (!isset($value[$part])) {
                $allPartsFound = false;
                break;
            }
            $value = $value[$part];
        }
        
        if ($allPartsFound && is_string($value)) {
            return $value;
        }
        
        return $key;
    }

    /**
     * Register the translation filter on the template engine
     */
    public function registerTranslationFilter($template, Context $context): void
    {
        // Register the translation filter
        $template->registerFilter('t', function ($input, $params = []) use ($context) {
            // Skip non-string inputs
            if (!is_string($input)) {
                return $input;
            }
            
            // Remove t: prefix if present
            $key = strpos($input, 't:') === 0 ? substr($input, 2) : $input;
            
            // Check if we have translations in context
            if (!isset($context->registers['translations']) || empty($context->registers['translations'])) {
                \Log::warning("Translation attempted but no translations in context", [
                    'key' => $key
                ]);
                return $key;
            }
            
            $translations = $context->registers['translations'];
            $parts = explode('.', $key);
            
            // Follow the path through the translations array
            $value = $translations;
            foreach ($parts as $part) {
                if (!isset($value[$part])) {
                    \Log::debug("Translation key not found", [
                        'key' => $key,
                        'missing_part' => $part
                    ]);
                    return $key; // Key not found, return original
                }
                $value = $value[$part];
            }
            
            // Return translation if found
            if (is_string($value)) {
                // Process any parameters
                if (!empty($params)) {
                    foreach ($params as $paramKey => $paramValue) {
                        $value = str_replace("{{ {$paramKey} }}", $paramValue, $value);
                    }
                }
                return $value;
            }
            
            // Log the issue if we didn't find a string value
            \Log::debug("Translation found but not a string", [
                'key' => $key,
                'value_type' => gettype($value)
            ]);
            
            return $key; // Return original key if not found
        });
        
        // Mark as registered in the context for debugging
        $context->registers['filters']['t'] = true;
    }
}