<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\Theme;

class ThemeAssetManager
{
    /**
     * Process asset references (CSS, JS, images) in content
     */
    public function processAssetReferences(string $content, array $theme): string 
    {
        // Check and normalize theme ID keys to ensure we have all necessary fields
        $storeId = $theme['store_id'] ?? null;
        $themeId = $theme['theme_id'] ?? $theme['id'] ?? null;
        
        if (!$storeId || !$themeId) {
            \Log::error("Missing theme identifiers in processAssetReferences", [
                'available_keys' => array_keys($theme)
            ]);
            // Provide fallbacks if data is missing
            $storeId = $storeId ?? request()->route('store_id') ?? 1;
            $themeId = $themeId ?? request()->route('theme_id') ?? 1;
        }
        
        $baseAssetUrl = url("assets/{$storeId}/{$themeId}");
        
        // Process asset references only (no CSS processing)
        $content = preg_replace_callback(
            '/\{\{\s*[\'"]?([^\'"}]*\.(css|js|jpg|jpeg|png|gif|svg|webp))[\'"]?\s*\|\s*asset_url(?:\s*\|\s*stylesheet_tag)?\s*\}\}/i',
            function($matches) use ($storeId, $themeId, $baseAssetUrl) {
                $asset = $matches[1];
                
                // Check if asset is already a complete URL
                if (strpos($asset, 'http') === 0 || strpos($asset, $baseAssetUrl) === 0) {
                    $assetUrl = $asset;
                } else {
                    $assetUrl = url("assets/{$storeId}/{$themeId}/{$asset}");
                }
                
                // Generate appropriate tag for stylesheet
                if (strpos($matches[0], 'stylesheet_tag') !== false) {
                    return "<link rel=\"stylesheet\" href=\"{$assetUrl}\" media=\"print\" onload=\"this.media='all'\">";
                }
                
                // Return just the URL for other assets
                return $assetUrl;
            },
            $content
        );
        
        return $content;
    }

    /**
     * Get inline SVG content
     */
    public function inlineSvgContent(string $svgFile, Theme $theme): string
    {
        $assetPath = "{$theme->store_id}/{$theme->shopify_theme_id}/assets/{$svgFile}";
        
        try {
            if (\Storage::disk('themes')->exists($assetPath)) {
                $svgContent = \Storage::disk('themes')->get($assetPath);
                
                // Check if it appears to be valid SVG
                if (stripos($svgContent, '<svg') !== false) {
                    return $svgContent;
                }
            }
            
            // Not found or not valid SVG, use image fallback
            $assetUrl = url("assets/{$theme->store_id}/{$theme->shopify_theme_id}/{$svgFile}");
            return "<img src=\"{$assetUrl}\" alt=\"\">";
        } catch (\Exception $e) {
            \Log::warning("Error getting inline SVG: {$svgFile}", [
                'error' => $e->getMessage()
            ]);
            
            // In case of error, return placeholder
            return "<svg class=\"placeholder-svg\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 525 525\"><rect width=\"525\" height=\"525\" fill=\"#F6F6F6\"/><text x=\"50%\" y=\"50%\" text-anchor=\"middle\" dy=\"0.3em\" fill=\"#A4A4A4\">SVG not found</text></svg>";
        }
    }

    /**
     * Add class to SVG
     */
    public function addClassToSvg(string $svgContent, string $class = ''): string
    {
        // If no class to add, return original content
        if (empty($class) || $class === 'placeholder-svg') {
            // Just ensure it has the placeholder-svg class
            if (strpos($svgContent, 'placeholder-svg') === false) {
                // If it already has some class attribute
                if (preg_match('/class=(["\'])(.*?)\\1/i', $svgContent, $matches)) {
                    $originalClass = $matches[2];
                    $newClass = 'placeholder-svg ' . $originalClass;
                    $svgContent = str_replace($matches[0], "class={$matches[1]}{$newClass}{$matches[1]}", $svgContent);
                } else {
                    // Add class attribute right after the <svg tag
                    $svgContent = preg_replace('/(<svg\s)/i', "$1class=\"placeholder-svg\" ", $svgContent);
                }
            }
            return $svgContent;
        }
        
        // Check if SVG already has class attribute
        if (preg_match('/class=(["\'])(.*?)\\1/i', $svgContent, $matches)) {
            $originalClass = $matches[2];
            
            // Check if it already has placeholder-svg class
            $hasPlaceholder = strpos($originalClass, 'placeholder-svg') !== false;
            
            // Check if it already has the custom class
            $hasCustomClass = strpos($originalClass, $class) !== false;
            
            // Build new class
            $newClass = $originalClass;
            if (!$hasPlaceholder) {
                $newClass = 'placeholder-svg ' . $newClass;
            }
            if (!$hasCustomClass) {
                $newClass .= ' ' . $class;
            }
            
            // Replace original class attribute
            $svgContent = str_replace($matches[0], "class={$matches[1]}{$newClass}{$matches[1]}", $svgContent);
        } else {
            // Doesn't have class attribute, add new one
            $newClass = 'placeholder-svg ' . $class;
            $svgContent = preg_replace('/(<svg\s)/i', "$1class=\"{$newClass}\" ", $svgContent);
        }
        
        return $svgContent;
    }

    /**
     * Generate HTML for SVG placeholder for any given name
     */
    public function generateSvgPlaceholder(string $svgName, string $class = '', Theme $theme = null): string
    {
        // Add .svg extension if it doesn't exist
        if (!preg_match('/\.svg$/i', $svgName)) {
            $svgFileName = $svgName . '.svg';
        } else {
            $svgFileName = $svgName;
            $svgName = preg_replace('/\.svg$/i', '', $svgName); // Remove extension for fallback lookup
        }
        
        // If theme not provided, use request data
        if (!$theme) {
            $storeId = request()->route('store_id');
            $themeId = request()->route('theme_id');
        } else {
            $storeId = $theme->store_id;
            $themeId = $theme->shopify_theme_id;
        }
        
        // Full path to SVG file
        $assetPath = "{$storeId}/{$themeId}/assets/{$svgFileName}";
        
        // 1. Try to read SVG file from disk
        try {
            if (\Storage::disk('themes')->exists($assetPath)) {
                $svgContent = \Storage::disk('themes')->get($assetPath);
                
                // Check if it appears to be valid SVG
                if (stripos($svgContent, '<svg') !== false) {
                    // Add classes to SVG
                    $svgContent = $this->addClassToSvg($svgContent, $class);
                    return $svgContent;
                }
            }
        } catch (\Exception $e) {
            \Log::warning("Error reading SVG from disk: {$svgFileName}", [
                'error' => $e->getMessage()
            ]);
        }
        
        // 2. Check fallbacks for known SVGs
        $fallbacks = [
            'hero-apparel-1' => '<svg class="placeholder-svg" preserveAspectRatio="xMaxYMid slice" viewBox="0 0 1300 730" fill="none" xmlns="http://www.w3.org/2000/svg"><!-- SVG content here --></svg>',
            'hero-apparel-2' => '<svg class="placeholder-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 525 525"><rect width="525" height="525" fill="#F6F6F6"/><path d="M375,375H150V150H375Z" fill="#D8D8D8"/><path d="M262.5,337.5a75,75,0,0,1,0-150h0a75,75,0,0,1,0,150Z" fill="none" stroke="#A4A4A4" stroke-miterlimit="10" stroke-width="4"/></svg>',
            'collection-1' => '<svg class="placeholder-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 525 525"><rect width="525" height="525" fill="#F6F6F6"/><rect x="141" y="276" width="243" height="110" fill="#EEEEEE"/><rect x="141" y="138" width="243" height="110" fill="#EEEEEE"/></svg>',
            'collection-2' => '<svg class="placeholder-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 525 525"><rect width="525" height="525" fill="#F6F6F6"/><rect x="137" y="138" width="251" height="246" fill="#EEEEEE"/></svg>',
            'image' => '<svg class="placeholder-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 525 525"><rect width="525" height="525" fill="#F6F6F6"/><path d="M324.5,323.5H200.5V200.5H324.5Z" fill="#EEEEEE"/></svg>',
            'product-1' => '<svg class="placeholder-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 525 525"><rect width="525" height="525" fill="#F6F6F6"/><path d="M151.5,339.5L229,243.5,286,302.5,394.5,193.5" fill="none" stroke="#C4C4C4" stroke-miterlimit="10" stroke-width="5"/><circle cx="395" cy="193" r="13" fill="#C4C4C4"/><circle cx="287" cy="302" r="13" fill="#C4C4C4"/><circle cx="229" cy="243" r="13" fill="#C4C4C4"/><circle cx="151" cy="340" r="13" fill="#C4C4C4"/></svg>'
        ];
        
        // Check fallbacks for requested SVG
        if (isset($fallbacks[$svgName])) {
            $svg = $fallbacks[$svgName];
            
            // Add custom class if provided
            if (!empty($class) && $class !== 'placeholder-svg') {
                $svg = str_replace('class="placeholder-svg"', 'class="placeholder-svg ' . $class . '"', $svg);
            }
            
            return $svg;
        }
        
        // 3. Generate generic placeholder SVG with the name
        $svg = "<svg class=\"placeholder-svg\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 525 525\"><rect width=\"525\" height=\"525\" fill=\"#F6F6F6\"/><text x=\"50%\" y=\"50%\" text-anchor=\"middle\" dy=\"0.3em\" fill=\"#A4A4A4\">{$svgName}</text></svg>";
        
        // Add custom class if provided
        if (!empty($class) && $class !== 'placeholder-svg') {
            $svg = str_replace('class="placeholder-svg"', 'class="placeholder-svg ' . $class . '"', $svg);
        }
        
        return $svg;
    }
}