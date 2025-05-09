<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Liquid\Context;
use App\Models\Theme;
use Illuminate\Support\Facades\Storage;

class ThemeSchemaManager
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
     * Enhanced schema processing for section rendering
     */
    public function loadAndApplySchema(Context $context, Theme $theme, string $sectionType, array $sectionData, $getThemeFile): void
    {
        // Initialize schema structure in context
        $context->registers['section_schema'] = [];
        
        // Load the section file
        $sectionPath = "sections/{$sectionType}.liquid";
        $sectionContent = $getThemeFile($theme, $sectionPath);
        
        // If file not found, there's no schema to process
        if (empty($sectionContent)) {
            \Log::warning("Couldn't load section file: {$sectionPath}");
            return;
        }
        
        // Extract schema from section content
        if (preg_match('/{%\s*schema\s*%}(.*?){%\s*endschema\s*%}/s', $sectionContent, $matches)) {
            $schemaJson = trim($matches[1]);
            
            try {
                // Decode JSON
                $schemaData = json_decode($schemaJson, true, 512, JSON_THROW_ON_ERROR);
                
                // Process translations in schema
                if ($this->translationService) {
                    $schemaData = $this->translationService->processTranslations($schemaData, $context);
                }
                
                // Store in context for access via {{ section_schema }}
                $context->set('section_schema', $schemaData);
                
                // Store in registers for access via custom tags
                $context->registers['section_schema'] = $schemaData;
                
                \Log::debug("Schema for section {$sectionType} loaded successfully", [
                    'has_settings' => isset($schemaData['settings']),
                    'settings_count' => isset($schemaData['settings']) ? count($schemaData['settings']) : 0
                ]);
                
                // Process default schema settings
                $this->applySchemaSettingsToContext($context, $schemaData, $sectionData);
                
            } catch (\JsonException $e) {
                \Log::error("Error decoding JSON schema for section {$sectionType}: " . $e->getMessage(), [
                    'json_sample' => substr($schemaJson, 0, 200)
                ]);
            } catch (\Exception $e) {
                \Log::error("Error processing schema for section {$sectionType}: " . $e->getMessage(), [
                    'trace' => $e->getTraceAsString()
                ]);
            }
        } else {
            \Log::debug("Section {$sectionType} doesn't contain schema");
        }
    }

    /**
     * Apply schema settings to context, with support for value inheritance
     */
    public function applySchemaSettingsToContext(Context $context, array $schemaData, array $sectionData): void
    {
        // If we don't have settings in the schema, nothing to do
        if (!isset($schemaData['settings']) || !is_array($schemaData['settings'])) {
            return;
        }
        
        // Get current settings or initialize new array
        $settings = $context->get('settings');
        if (!is_array($settings)) {
            $settings = [];
        }
        
        // Process each setting
        foreach ($schemaData['settings'] as $setting) {
            if (!isset($setting['id'])) {
                continue;
            }
            
            $id = $setting['id'];
            
            // Check first in section data
            if (isset($sectionData['settings']) && isset($sectionData['settings'][$id])) {
                $settings[$id] = $sectionData['settings'][$id];
            } 
            // Then try directly in section data (for compatibility)
            elseif (isset($sectionData[$id])) {
                $settings[$id] = $sectionData[$id];
            }
            // Lastly, use default value if available
            elseif (isset($setting['default']) && !isset($settings[$id])) {
                $settings[$id] = $setting['default'];
            }
        }
        
        // Check settings in blocks
        if (isset($schemaData['blocks']) && isset($sectionData['blocks']) && isset($sectionData['block_order'])) {
            $blocks = [];
            
            // Process each block in specified order
            foreach ($sectionData['block_order'] as $blockId) {
                if (!isset($sectionData['blocks'][$blockId])) {
                    continue;
                }
                
                $blockData = $sectionData['blocks'][$blockId];
                $blockType = $blockData['type'] ?? '';
                
                // Skip blocks without type
                if (empty($blockType)) {
                    continue;
                }
                
                // Find settings for this block type
                $blockSettingsSchema = null;
                foreach ($schemaData['blocks'] as $blockSchema) {
                    if (($blockSchema['type'] ?? '') === $blockType) {
                        $blockSettingsSchema = $blockSchema['settings'] ?? [];
                        break;
                    }
                }
                
                // If we found schema for this block type, process it
                if ($blockSettingsSchema) {
                    // Copy block data
                    $block = $blockData;
                    $block['id'] = $blockId;
                    
                    // Ensure we have a settings array
                    if (!isset($block['settings']) || !is_array($block['settings'])) {
                        $block['settings'] = [];
                    }
                    
                    // Apply default values to missing settings
                    foreach ($blockSettingsSchema as $blockSetting) {
                        if (isset($blockSetting['id']) && isset($blockSetting['default'])) {
                            $settingId = $blockSetting['id'];
                            
                            // Use existing value or default
                            if (!isset($block['settings'][$settingId])) {
                                $block['settings'][$settingId] = $blockSetting['default'];
                            }
                        }
                    }
                    
                    $blocks[] = $block;
                } else {
                    // Add the block even without schema
                    $block = $blockData;
                    $block['id'] = $blockId;
                    $blocks[] = $block;
                }
            }
            
            // Add processed blocks to context
            if (!empty($blocks)) {
                $context->set('blocks', $blocks);
            }
        }
        
        // Update settings in context
        $context->set('settings', $settings);
        
        // Also update in registers for access via tags
        $context->registers['settings'] = $settings;
        
        \Log::debug("Schema settings applied to context", [
            'settings_count' => count($settings),
            'section_id' => $sectionData['id'] ?? 'unknown'
        ]);
    }
}