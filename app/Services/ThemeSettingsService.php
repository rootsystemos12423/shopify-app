<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Liquid\Context;

/**
 * Service for managing Shopify theme settings and translations
 */
class ThemeSettingsService
{
    /**
     * Cache TTL in seconds (2 hours)
     */
    const CACHE_TTL = 7200;
    
    /**
     * Default locale when none specified
     */
    const DEFAULT_LOCALE = 'pt-BR';
    
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
     * Load settings schema from theme
     *
     * @param string $themePath Path to theme directory
     * @return array Schema data
     */
    public function loadSchema(string $themePath): array
    {
        $cacheKey = "theme_schema_{$themePath}";
        
        if (Cache::has($cacheKey)) {
            $cachedSchema = Cache::get($cacheKey);
            if (is_array($cachedSchema)) {
                return $cachedSchema;
            }
            
            // Clear invalid cache
            Cache::forget($cacheKey);
        }
        
        $schemaPath = "{$themePath}/config/settings_schema.json";
        
        if (!Storage::disk('themes')->exists($schemaPath)) {
            Log::info("Settings schema not found: {$schemaPath}");
            return [];
        }
        
        try {
            $content = Storage::disk('themes')->get($schemaPath);
            
            // Ensure we have content
            if (empty($content)) {
                Log::warning("Empty settings schema: {$schemaPath}");
                return [];
            }
            
            // Decode JSON content with comments support
            $schema = $this->jsonDecodeWithComments($content);
            
            // Check for JSON decode errors
            if (!is_array($schema)) {
                Log::error('Failed to decode settings_schema.json', [
                    'path' => $schemaPath,
                    'error' => json_last_error_msg(),
                    'content_sample' => substr($content, 0, 100) . '...'
                ]);
                return [];
            }
            
            // Store in cache
            Cache::put($cacheKey, $schema, self::CACHE_TTL);
            
            return $schema;
            
        } catch (\Exception $e) {
            Log::error('Exception loading schema', [
                'path' => $schemaPath,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * Load and apply schema for a section
     *
     * @param Context $context Liquid context
     * @param string $themePath Theme path
     * @param string $sectionType Section type
     * @param array $sectionData Section data
     * @return void
     */
    public function loadAndApplySchema(Context $context, string $themePath, string $sectionType, array $sectionData): void
    {
        // Construct section path
        $sectionPath = "{$themePath}/sections/{$sectionType}.liquid";
        
        // Extract schema from section content
        $schema = $this->extractSchemaFromSection($sectionPath);
        
        if ($schema) {
            // Process translations in schema
            $schema = $this->processSchemaTranslations($schema, $context);
            
            // Store in context
            $context->set('section_schema', $schema);
            $context->registers['section_schema'] = $schema;
            
            // Apply schema defaults
            $this->applySchemaValuesToContext($context, $schema, $sectionData);
        }
    }

    /**
     * Load current theme settings
     *
     * @param string $themePath Path to theme directory
     * @return array Settings data
     */
    public function loadSettings(string $themePath): array
    {
        try {
            // Check cache first
            $cacheKey = "theme_settings_{$themePath}";
            if (Cache::has($cacheKey)) {
                $cachedValue = Cache::get($cacheKey);
                if (is_array($cachedValue)) {
                    return $cachedValue;
                }
                
                // Clear invalid cache
                Cache::forget($cacheKey);
            }
            
            // File path
            $settingsPath = "{$themePath}/config/settings_data.json";
            
            \Log::debug("Tentando carregar settings de: {$settingsPath}", [
                'exists' => Storage::disk('themes')->exists($settingsPath)
            ]);
            
            if (!Storage::disk('themes')->exists($settingsPath)) {
                Log::info("settings_data.json file not found: {$settingsPath}");
                return [];
            }
            
            // Read file content
            $content = Storage::disk('themes')->get($settingsPath);
            
            // Log raw content sample for debugging
            \Log::debug("Content sample:", ['sample' => substr($content, 0, 200)]);
            
            // Decode JSON with comments support
            $data = $this->jsonDecodeWithComments($content);
            
            if (!is_array($data)) {
                Log::error("settings_data.json did not decode to an array");
                return [];
            }
            
            // Log structure of decoded data
            \Log::debug("Data structure:", [
                'root_keys' => array_keys($data),
                'has_presets' => isset($data['presets']),
                'presets_type' => gettype($data['presets'] ?? null)
            ]);
            
            // Safely get current preset name
            $currentPreset = 'Default'; // Default fallback
            if (isset($data['current']) && is_string($data['current'])) {
                $currentPreset = $data['current'];
            }
            
            // Safely access presets
            if (!isset($data['presets']) || !is_array($data['presets'])) {
                Log::error("Missing or invalid presets structure");
                return [];
            }
            
            // Safely access the current preset
            if (!isset($data['presets'][$currentPreset]) || !is_array($data['presets'][$currentPreset])) {
                Log::error("Invalid preset: {$currentPreset}");
                // Try to use any available preset as fallback
                $presetKeys = array_keys($data['presets']);
                if (!empty($presetKeys)) {
                    $currentPreset = $presetKeys[0];
                    Log::info("Using fallback preset: {$currentPreset}");
                } else {
                    return [];
                }
            }
            
            $settings = $data['presets'][$currentPreset];
            
            // Extra safety: ensure settings is an array
            if (!is_array($settings)) {
                Log::error("Settings is not an array");
                return [];
            }
            
            // Log settings structure
            \Log::debug("Settings structure:", [
                'settings_type' => gettype($settings),
                'settings_count' => count($settings),
                'first_few_keys' => array_slice(array_keys($settings), 0, 5),
                'has_color_schemes' => isset($settings['color_schemes']),
                'color_schemes_type' => gettype($settings['color_schemes'] ?? null)
            ]);
            
            // Check for problematic structures in color_schemes
            if (isset($settings['color_schemes'])) {
                if (!is_array($settings['color_schemes'])) {
                    Log::warning("color_schemes is not an array, fixing");
                    $settings['color_schemes'] = [];
                } else {
                    // Log color schemes structure
                    foreach ($settings['color_schemes'] as $key => $scheme) {
                        \Log::debug("Color scheme key: " . gettype($key) . ", value type: " . gettype($scheme));
                        
                        // Check for invalid keys or structures
                        if (!is_string($key) && !is_int($key)) {
                            Log::error("Invalid color scheme key type: " . gettype($key));
                            // Fix by removing problematic entry
                            unset($settings['color_schemes'][$key]);
                        }
                        
                        if (!is_array($scheme)) {
                            Log::error("Invalid color scheme structure for key: " . $key);
                            // Fix by initializing as empty array
                            $settings['color_schemes'][$key] = [];
                        }
                    }
                }
            }
            
            // Store in cache
            Cache::put($cacheKey, $settings, self::CACHE_TTL);
            
            return $settings;
        } catch (\Exception $e) {
            Log::error("Exception in loadSettings: " . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return [];
        }
    }
    
    /**
     * Process schema and settings for Liquid context
     *
     * @param string $themePath Path to theme directory
     * @param string|null $locale Language code
     * @return array Processed settings for Liquid
     */
    public function processForLiquid(string $themePath, string $locale = null): array
    {
        $locale = $locale ?? self::DEFAULT_LOCALE;
        $cacheKey = "theme_liquid_settings_{$themePath}_{$locale}";
        
        // LIMPE O CACHE MANUALMENTE PARA ESTE TEMA
        Cache::forget($cacheKey);
        \Log::debug("Cache limpo para chave: {$cacheKey}");
        
        if (Cache::has($cacheKey)) {
            $cachedSettings = Cache::get($cacheKey);
            if (is_array($cachedSettings)) {
                \Log::debug("Retornando settings do cache (isso não deveria acontecer)");
                return $cachedSettings;
            }
            
            // Clear invalid cache
            Cache::forget($cacheKey);
        }
        
        // Load raw schema and settings
        $schema = $this->loadSchema($themePath);
        $settings = $this->loadSettings($themePath);
        
        // Process settings with schema defaults
        $liquidSettings = [];
        
        foreach ($schema as $section) {
            // Handle theme_info section specially
            if (isset($section['name']) && $section['name'] === 'theme_info') {
                $this->processThemeInfo($section, $liquidSettings);
                continue;
            }
            
            if (!isset($section['settings']) || !is_array($section['settings'])) {
                continue;
            }
            
            foreach ($section['settings'] as $setting) {
                if (!isset($setting['id'])) {
                    continue;
                }
                
                $id = $setting['id'];
                
                // Use value from settings_data.json or default
                $value = $settings[$id] ?? $setting['default'] ?? null;
                
                // Process special setting types
                if (isset($setting['type'])) {
                    $value = $this->processSettingType($setting, $value);
                }
                
                // Add to liquid settings
                $liquidSettings[$id] = $value;
            }
        }
        
        // Processar color_schemes para converter cores hex para RGB
        if (isset($liquidSettings['color_schemes']) && is_array($liquidSettings['color_schemes'])) {
            foreach ($liquidSettings['color_schemes'] as $schemeId => &$scheme) {
                if (isset($scheme['settings']) && is_array($scheme['settings'])) {
                    foreach ($scheme['settings'] as $key => &$value) {
                        // Se for uma cor hex
                        if (is_string($value) && strpos($value, '#') === 0) {
                            $rgb = $this->hexToRgb($value);
                            
                            $value = [
                                'red' => $rgb['r'],
                                'green' => $rgb['g'],
                                'blue' => $rgb['b'],
                                'rgb' => "{$rgb['r']},{$rgb['g']},{$rgb['b']}",
                                '_hex' => $value, // Manter o valor hex original
                                'toString' => "rgb({$rgb['r']},{$rgb['g']},{$rgb['b']})" // Para fallbacks
                            ];
                        }
                    }
                    
                    // Garantir que background_gradient esteja disponível
                    if (!isset($scheme['settings']['background_gradient'])) {
                        $scheme['settings']['background_gradient'] = '';
                    }
                    
                    // Adicionar o ID ao schema processado
                    $scheme['id'] = $schemeId;
                }
            }
        }

        if (isset($liquidSettings['type_body_font']) && is_string($liquidSettings['type_body_font'])) {
            // Parse font string (e.g., "assistant_n4")
            $parts = explode('_', $liquidSettings['type_body_font']);
            $family = $parts[0];
            
            // Parse weight properly - convert "n4" to 400
            $weightCode = isset($parts[1]) ? $parts[1] : 'n4';
            if (substr($weightCode, 0, 1) === 'n') {
                $numericPart = substr($weightCode, 1);
                // Convert single digit weights to hundreds (4 -> 400)
                $weight = strlen($numericPart) === 1 ? intval($numericPart) * 100 : intval($numericPart);
            } else {
                $weight = 400; // Default weight
            }
            
            // Create structured font object
            $liquidSettings['type_body_font'] = [
                'family' => ucfirst($family), // Capitalize first letter
                'fallback_families' => 'sans-serif',
                'weight' => $weight,
                'style' => 'normal'
            ];
            
            // Set bold weight to 700 if parsing from "n4" or calculate it
            if ($weight === 400) {
                $boldWeight = 700; // Standard bold weight for regular font
            } else {
                // Calculate a bold weight that's heavier but max 900
                $boldWeight = min(($weight + 300), 900);
            }
            $liquidSettings['body_font_bold_weight'] = $boldWeight;
        }
        
        // Do the same for header font
        if (isset($liquidSettings['type_header_font']) && is_string($liquidSettings['type_header_font'])) {
            $parts = explode('_', $liquidSettings['type_header_font']);
            $family = $parts[0];
            
            // Parse weight properly
            $weightCode = isset($parts[1]) ? $parts[1] : 'n4';
            if (substr($weightCode, 0, 1) === 'n') {
                $numericPart = substr($weightCode, 1);
                $weight = strlen($numericPart) === 1 ? intval($numericPart) * 100 : intval($numericPart);
            } else {
                $weight = 400;
            }
            
            $liquidSettings['type_header_font'] = [
                'family' => ucfirst($family),
                'fallback_families' => 'sans-serif',
                'weight' => $weight,
                'style' => 'normal'
            ];
        }

        $liquidSettings = $this->normalizeColorSchemes($liquidSettings);
        
        // Process translations in settings
        if ($this->translationService) {
            $translations = $this->translationService->loadTranslations($themePath, $locale);
            $context = new Context(['translations' => $translations]);
            $liquidSettings = $this->translationService->processTranslations($liquidSettings, $context);
        }
        
        // Cache the processed settings
        Cache::put($cacheKey, $liquidSettings, self::CACHE_TTL);
        
        return $liquidSettings;
    }
        
    /**
     * Process a setting value based on its type
     */
    private function processSettingType(array $setting, $value)
    {
        $type = $setting['type'];
        
        switch ($type) {
            case 'color_scheme_group':
                // Aqui está o problema! O color_scheme_group já é processado com os valores corretos
                if (is_array($value)) {
                    return $value;
                }
                // Se não for array, retorna um array vazio
                return [];
                
            case 'range':
                // Ensure numeric value
                return is_numeric($value) ? (float)$value : (float)($setting['default'] ?? 0);
                
            case 'header':
                // Header doesn't have a value, just metadata
                return null;
                
            case 'checkbox':
                // Ensure boolean value
                return (bool)$value;
                
            case 'select':
                // Validate against options
                if (isset($setting['options']) && is_array($setting['options'])) {
                    $options = array_column($setting['options'], 'value');
                    if (!in_array($value, $options)) {
                        $value = $setting['default'] ?? $options[0] ?? null;
                    }
                }
                return $value;
                
            default:
                // Return unchanged for other types
                return $value;
        }
    }

    public function normalizeColorSchemes(array $settings): array
    {
        // Early exit if no color_schemes key
        if (!array_key_exists('color_schemes', $settings)) {
            \Log::debug("color_schemes key not found in settings array");
            $settings['color_schemes'] = [];
            return $settings;
        }
        
        \Log::debug("color_schemes found, type: " . gettype($settings['color_schemes']));
        
        // Convert to array if not already
        if (!is_array($settings['color_schemes'])) {
            \Log::debug("color_schemes is not an array, converting to empty array");
            $settings['color_schemes'] = [];
            return $settings;
        }
        
        // Convert to the format the template expects
        $normalizedSchemes = [];
        
        try {
            foreach ($settings['color_schemes'] as $schemeId => $scheme) {
                // Skip invalid keys or values
                if (!is_string($schemeId) && !is_int($schemeId)) {
                    \Log::debug("Skipping invalid scheme key type: " . gettype($schemeId));
                    continue;
                }
                
                if (!is_array($scheme)) {
                    \Log::debug("Skipping non-array scheme for key: {$schemeId}");
                    continue;
                }
                
                $normalized = $scheme;
                // Ensure ID is available as string
                $normalized['id'] = (string)$schemeId;
                $normalizedSchemes[] = $normalized;
            }
        } catch (\Exception $e) {
            \Log::error("Error in normalizeColorSchemes: " . $e->getMessage());
            // In case of error, reset to empty array
            $normalizedSchemes = [];
        }
        
        $settings['color_schemes'] = $normalizedSchemes;
        return $settings;
    }

    /**
     * Process theme_info section
     *
     * @param array $section Theme info section
     * @param array &$liquidSettings Settings to update
     * @return void
     */
    private function processThemeInfo(array $section, array &$liquidSettings): void
    {
        $infoFields = [
            'theme_name', 
            'theme_version', 
            'theme_author', 
            'theme_documentation_url', 
            'theme_support_url', 
            'theme_support_email'
        ];
        
        foreach ($infoFields as $field) {
            if (isset($section[$field])) {
                $liquidSettings[$field] = $section[$field];
            }
        }
    }
    
    public function processColorSchemeGroup(array $colorSchemes): array
    {
        $processedSchemes = [];
        
        // Iterar sobre cada esquema de cores
        foreach ($colorSchemes as $id => $scheme) {
            if (!isset($scheme['settings'])) {
                continue;
            }
            
            $processedSettings = [];
            
            // Processar cada configuração dentro do esquema
            foreach ($scheme['settings'] as $key => $value) {
                // Verificar se é uma cor (color no nome ou background)
                if (strpos($key, 'color') !== false || $key === 'background') {
                    if (is_string($value) && strpos($value, '#') === 0) {
                        // Converter hex para RGB
                        $rgb = $this->hexToRgb($value);
                        
                        // Formato que o Shopify espera
                        $processedSettings[$key] = [
                            'red' => $rgb['r'],
                            'green' => $rgb['g'],
                            'blue' => $rgb['b'],
                            'rgb' => "{$rgb['r']},{$rgb['g']},{$rgb['b']}"
                        ];
                    }
                } else {
                    $processedSettings[$key] = $value;
                }
            }
            
            // Adicionar ao array de esquemas processados, mantendo a estrutura correta
            $processedSchemes[] = [
                'id' => $id,
                'settings' => $processedSettings
            ];
        }
        
        return $processedSchemes;
    }

    /**
     * Format RGB components into a clean, CSS-ready string
     */
    private function formatRgbComponents($rgb)
    {
        // Se não for array ou não tiver os elementos necessários
        if (!is_array($rgb) || !isset($rgb['r'], $rgb['g'], $rgb['b'])) {
            return '0, 0, 0';
        }
        
        
        // Sanitizar cada componente
        $r = $this->sanitizeRgbComponent($rgb['r']);
        $g = $this->sanitizeRgbComponent($rgb['g']);
        $b = $this->sanitizeRgbComponent($rgb['b']);
        
        // Retornar formato correto COM ESPAÇOS
        return "{$r}, {$g}, {$b}"; // Com espaços!
    }

    /**
     * Sanitizar um componente RGB
     */
    private function sanitizeRgbComponent($value)
    {
        // Lidar com valores vazios
        if ($value === null || $value === '' || !is_numeric($value)) {
            return 0;
        }
        
        // Garantir que o valor está no range válido (0-255)
        return max(0, min(255, intval($value)));
    }

    private function extractRgbComponents($rgbString)
    {
        preg_match('/rgb\(([^,]*),([^,]*),([^,\)]*)\)/', $rgbString, $matches);
        
        if (count($matches) >= 4) {
            return [
                $this->sanitizeRgbComponent($matches[1]),
                $this->sanitizeRgbComponent($matches[2]),
                $this->sanitizeRgbComponent($matches[3])
            ];
        }
        
        return [0, 0, 0]; // Default if extraction fails :antCitation[]{citations="80c6549e-8c3f-4044-a4d8-eeab9c229b93"}
    }

    private function isColorValue($value)
    {
        if (is_string($value)) {
            // Check for hex color or rgb string
            return strpos($value, '#') === 0 || preg_match('/rgb/i', $value);
        }
        
        if (is_array($value)) {
            // Check for array with RGB components
            return isset($value['red']) || isset($value['r']) || 
                (isset($value[0]) && isset($value[1]) && isset($value[2]));
        }
        
        return false;
    }

    private function rgbToHsl($r, $g, $b): array
    {
        $r /= 255;
        $g /= 255;
        $b /= 255;
        
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $h = 0;
        $s = 0;
        $l = ($max + $min) / 2;
        
        if ($max != $min) {
            $d = $max - $min;
            $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
            
            switch ($max) {
                case $r:
                    $h = (($g - $b) / $d + ($g < $b ? 6 : 0)) / 6;
                    break;
                case $g:
                    $h = (($b - $r) / $d + 2) / 6;
                    break;
                case $b:
                    $h = (($r - $g) / $d + 4) / 6;
                    break;
            }
        }
        
        return [
            'h' => round($h * 360),
            's' => round($s * 100),
            'l' => round($l * 100)
        ];
    }

    private function hexToRgb($hex)
    {
        $hex = ltrim($hex, '#');
        
        // Validate hex color
        if (!preg_match('/^[0-9A-Fa-f]{3}$|^[0-9A-Fa-f]{6}$/', $hex)) {
            return ['r' => 0, 'g' => 0, 'b' => 0]; // Default to black for invalid hex
        }
        
        // Handle shorthand hex (#FFF)
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        
        // Convert to RGB
        return [
            'r' => hexdec(substr($hex, 0, 2)), // Red
            'g' => hexdec(substr($hex, 2, 2)), // Green
            'b' => hexdec(substr($hex, 4, 2))  // Blue
        ];
    }
    
    /**
     * Initialize Liquid context with theme settings
     *
     * @param Context $context Liquid context
     * @param string $themePath Theme directory path
     * @param string|null $locale Language code
     * @return void
     */
    public function initializeContext(Context $context, string $themePath, string $locale = null): void
    {
        $locale = $locale ?? self::DEFAULT_LOCALE;
        
        try {
            // Load and process settings for Liquid
            $settings = $this->processForLiquid($themePath, $locale);
            
            // Register in context
            $context->set('settings', $settings);
            
            // Also load theme schema for templates that need it
            $schema = $this->loadSchema($themePath);
            $context->registers['theme_schema'] = $schema;
            
            // Store settings in registers for custom tags
            $context->registers['settings'] = $settings;
            
            Log::debug('Theme settings initialized for context', [
                'themePath' => $themePath,
                'settings_count' => count($settings)
            ]);
        } catch (\Exception $e) {
            Log::error('Error initializing theme settings for context', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'themePath' => $themePath
            ]);
            
            // Initialize with empty arrays to prevent template errors
            $context->set('settings', []);
            $context->registers['settings'] = [];
            $context->registers['theme_schema'] = [];
        }
    }
    
    /**
     * Clear settings cache
     *
     * @param string|null $themePath Theme directory path (optional)
     * @param string|null $locale Language code (optional)
     * @return void
     */
    public function clearCache(string $themePath = null, string $locale = null): void
    {
        if ($themePath && $locale) {
            // Clear specific cache
            Cache::forget("theme_liquid_settings_{$themePath}_{$locale}");
        } elseif ($themePath) {
            // Clear all locales for a theme
            Cache::forget("theme_schema_{$themePath}");
            Cache::forget("theme_settings_{$themePath}");
            
            // Clear all locale-specific caches
            $keys = Cache::get('cache_keys', []);
            foreach ($keys as $key) {
                if (strpos($key, "theme_liquid_settings_{$themePath}_") === 0) {
                    Cache::forget($key);
                }
            }
        } else {
            // Clear all theme settings
            $keys = Cache::get('cache_keys', []);
            foreach ($keys as $key) {
                if (strpos($key, 'theme_schema_') === 0 || 
                    strpos($key, 'theme_settings_') === 0 || 
                    strpos($key, 'theme_liquid_settings_') === 0) {
                    Cache::forget($key);
                }
            }
        }
        
        Log::info('Theme settings cache cleared', [
            'theme' => $themePath ?? 'all',
            'locale' => $locale ?? 'all'
        ]);
    }
    
    /**
     * JSON decode with support for comments and trailing commas
     *
     * @param string $json JSON string
     * @return array|null Decoded data
     */
    private function jsonDecodeWithComments(string $json): ?array
    {
        // Remove comments
        $json = preg_replace('!//.*?$!m', '', $json);
        $json = preg_replace('!/\*.*?\*/!s', '', $json);
        
        // Remove trailing commas in objects and arrays
        $json = preg_replace('!,\s*([\]}])!', '$1', $json);
        
        // Decode
        $data = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('JSON decode error: ' . json_last_error_msg());
            return null;
        }
        
        return $data;
    }
    
    /**
     * Extract schema from section content
     * 
     * @param string $sectionPath Path to section file
     * @return array|null Extracted schema
     */
    private function extractSchemaFromSection(string $sectionPath): ?array
    {
        if (!Storage::disk('themes')->exists($sectionPath)) {
            return null;
        }
        
        try {
            $content = Storage::disk('themes')->get($sectionPath);
            
            // Extract schema block from content
            if (preg_match('/{%\s*schema\s*%}(.*?){%\s*endschema\s*%}/s', $content, $matches)) {
                $jsonContent = trim($matches[1]);
                
                // Decode JSON
                $schema = $this->jsonDecodeWithComments($jsonContent);
                
                return $schema;
            }
        } catch (\Exception $e) {
            Log::error("Error extracting schema from section: {$sectionPath}", [
                'error' => $e->getMessage()
            ]);
        }
        
        return null;
    }
    
    /**
     * Process translations in schema
     * 
     * @param array $schema Schema data
     * @param Context $context Liquid context
     * @return array Processed schema
     */
    private function processSchemaTranslations(array $schema, Context $context): array
    {
        if (!$this->translationService) {
            return $schema;
        }
        
        return $this->translationService->processTranslations($schema, $context);
    }
    
    /**
     * Apply schema values to context
     * 
     * @param Context $context Liquid context
     * @param array $schema Schema data
     * @param array $sectionData Section data
     * @return void
     */
    private function applySchemaValuesToContext(Context $context, array $schema, array $sectionData): void
    {
        // Get existing settings
        $settings = $context->get('settings');
        if (!is_array($settings)) {
            $settings = [];
        }
        
        // Process settings from schema
        if (isset($schema['settings']) && is_array($schema['settings'])) {
            foreach ($schema['settings'] as $setting) {
                if (!isset($setting['id'])) {
                    continue;
                }
                
                $id = $setting['id'];
                
                // Check if setting is in section data
                if (isset($sectionData['settings'][$id])) {
                    $settings[$id] = $sectionData['settings'][$id];
                } elseif (isset($sectionData[$id])) {
                    // Fallback to direct property
                    $settings[$id] = $sectionData[$id];
                } elseif (isset($setting['default']) && !isset($settings[$id])) {
                    // Use default if not set
                    $settings[$id] = $setting['default'];
                }
            }
        }
        
        // Update context
        $context->set('settings', $settings);
        $context->registers['settings'] = $settings;
    }
}