<?php

namespace App\Services;

use Illuminate\Http\Request;
use App\Models\Theme;
use App\Models\Domain;
use Liquid\Context;
use Illuminate\Support\Facades\Log;

class ThemeContextManager
{
    /**
     * Build the base context for rendering
     */
    public function buildContext(Request $request, Theme $theme, $generateContentForHeader): array
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
            'content_for_header' => $generateContentForHeader($theme, $request),
        ];
    }

    /**
     * Create a new Liquid context with provided data
     */
    public function createContext(array $baseContext = []): Context
    {
        $context = new Context();
        
        // Add all base data to context
        foreach ($baseContext as $key => $value) {
            $context->set($key, $value);
        }
        
        return $context;
    }

    /**
     * Get the shop domain
     */
    public function getShop(Request $request): Domain
    {
        return Domain::where('domain', $request->getHost())->firstOrFail();
    }

    /**
     * Get the active theme for the shop
     */
    public function getTheme(Domain $shop): Theme
    {
        return Theme::where('store_id', $shop->store_id)
            ->where('role', 'main')
            ->firstOrFail();
    }

    /**
     * Add global objects to context
     */
    public function addGlobalObjects(Context $context, GlobalObjectsProvider $provider, Request $request, Theme $theme, string $template): void
    {
        $provider->provide($context, [
            'request' => $request,
            'theme' => $theme,
            'template' => $template
        ]);
    }

    /**
     * Create section context from parent context
     */
    public function createSectionContext(Context $parentContext, array $sectionData): Context
    {
        $sectionContext = new Context();
        
        // Preserve values from the original context
        foreach ($parentContext->getAll() as $key => $value) {
            if ($key !== 'section') {
                $sectionContext->set($key, $value);
            }
        }
        
        // Copy important registers from original context
        foreach ($parentContext->registers as $key => $value) {
            $sectionContext->registers[$key] = $value;
        }
        
        // Set the section in context
        $sectionContext->set('section', $sectionData);
        
        return $sectionContext;
    }

    /**
     * Process blocks for section data
     */
    public function processBlocksForSection(array &$sectionData): void
    {
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
    }
}