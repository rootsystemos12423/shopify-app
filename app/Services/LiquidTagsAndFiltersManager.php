<?php

namespace App\Services;

use Liquid\Template;
use Liquid\Context;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Liquid\CustomFilters;

// Custom Tags
use App\Liquid\CustomTags\FormTag;
use App\Liquid\CustomTags\StyleTag;
use App\Liquid\CustomTags\SectionTag;
use App\Liquid\CustomTags\LiquidTag;
use App\Liquid\CustomTags\RenderTag;
use App\Liquid\CustomTags\SchemaTag;
use App\Liquid\CustomTags\SectionsTag;
use App\Liquid\CustomTags\JavaScriptTag;
use App\Liquid\CustomTags\TagFor;
use App\Liquid\CustomTags\EchoTag;


// Native Liquid Tags
use Liquid\Tag\TagIf;
use Liquid\Tag\TagCase;
use Liquid\Tag\TagUnless;
use Liquid\Tag\TagCapture;
use Liquid\Tag\CaptureBlock;
use Liquid\Tag\TagComment;
use Liquid\Tag\CommentBlock;
use Liquid\Tag\TagAssign;
use Liquid\Tag\TagCycle;
use Liquid\Tag\TagContinue;
use Liquid\Tag\TagBreak;
use Liquid\Tag\TagRaw;
use Liquid\Tag\TagInclude;
use Liquid\Tag\TagIncrement;
use Liquid\Tag\TagDecrement;
use Liquid\Tag\TagPaginate;
use Liquid\Tag\TagTablerow;
use Liquid\Tag\TagBlock;

class LiquidTagsAndFiltersManager
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
     * Register all Liquid tags and filters on a template engine
     */
    public function registerTagsAndFilters(Template $engine, Context $context): void
    {
        $this->registerTags($engine);
        $this->registerFilters($engine, $context);
    }

    /**
     * Register all tags
     */
    public function registerTags(Template $engine): void
    {
        $this->registerNativeTags($engine);
        $this->registerCustomTags($engine);
    }

    /**
     * Register native Liquid tags
     */
    private function registerNativeTags(Template $engine): void
    {
        $engine->registerTag('assign', TagAssign::class);

        $tags = [
            'if' => TagIf::class,
            'unless' => TagUnless::class, 
            'case' => TagCase::class,
            'capture' => TagCapture::class,
            'comment' => TagComment::class,
            'cycle' => TagCycle::class,
            'continue' => TagContinue::class,
            'break' => TagBreak::class,
            'raw' => TagRaw::class,
            'include' => TagInclude::class,
            'increment' => TagIncrement::class,
            'decrement' => TagDecrement::class,
            'paginate' => TagPaginate::class,
            'tablerow' => TagTablerow::class,
            'block' => TagBlock::class,
            'javascript' => JavaScriptTag::class,
        ];

        foreach ($tags as $name => $class) {
            $engine->registerTag($name, $class);
        }
    }

    /**
     * Register custom tags
     */
    private function registerCustomTags(Template $engine): void
    {
        $tags = [
            'for' => TagFor::class,
            'schema' => SchemaTag::class,
            'form' => FormTag::class,
            'section' => SectionTag::class,
            'style' => StyleTag::class,
            'liquid' => LiquidTag::class,
            'render' => RenderTag::class,
            'sections' => SectionsTag::class,
            'echo' => EchoTag::class,
        ];

        foreach ($tags as $name => $class) {
            $engine->registerTag($name, $class);
        }
    }

    /**
     * Register filters in context
     */
    public function registerFilters(Template $engine, Context $context): void
    {
        // Standard existing filters
        $filters = new CustomFilters($context);
        $engine->registerFilter($filters);
        
        // Register asset URL filter
        $this->registerAssetUrlFilter($engine, $context);
        
        // Register stylesheet tag filter
        $this->registerStylesheetTagFilter($engine);
        
        // Register inline asset content filter
        $this->registerInlineAssetContentFilter($engine, $context);
        
        // Register translation filter with improvements
        $this->registerTranslationFilter($engine, $context);
        
        // Register theme settings filters
        $this->registerSettingsFilters($engine, $context);
    }

    /**
     * Register asset URL filter
     */
    private function registerAssetUrlFilter(Template $engine, Context $context): void
    {
        $engine->registerFilter('asset_url', function($input) use ($context) {
            $theme = $context->get('theme');
            $storeId = $theme['store_id'];
            $themeId = $theme['id'];
            
            // Remove quotes from beginning and end if they exist
            $assetName = is_string($input) ? trim($input, '\'"') : '';
            
            if (empty($assetName)) {
                return '';
            }
            
            return url("assets/{$storeId}/{$themeId}/{$assetName}");
        });
    }

    /**
     * Register stylesheet tag filter
     */
    private function registerStylesheetTagFilter(Template $engine): void
    {
        $engine->registerFilter('stylesheet_tag', function($input) {
            if (empty($input)) return '';
            
            // Check if input is already a complete URL
            return "<link rel=\"stylesheet\" href=\"{$input}\" media=\"all\" onload=\"this.media='all'\">";
        });
    }

    /**
     * Register inline asset content filter
     */
    private function registerInlineAssetContentFilter(Template $engine, Context $context): void
    {
        $engine->registerFilter('inline_asset_content', function($input) use ($context) {
            $theme = $context->get('theme');
            $storeId = $theme['store_id'];
            $themeId = $theme['id'];
            
            // Remove quotes from beginning and end if they exist
            $assetName = is_string($input) ? trim($input, '\'"') : '';
            
            if (empty($assetName)) {
                return '';
            }
            
            // Try to load the file content
            $assetPath = "{$storeId}/{$themeId}/assets/{$assetName}";
            
            try {
                if (Storage::disk('themes')->exists($assetPath)) {
                    return Storage::disk('themes')->get($assetPath);
                }
            } catch (\Exception $e) {
                \Log::warning("Couldn't load inline asset: {$assetPath}");
            }
            
            return "<!-- Asset {$assetName} not found -->";
        });
    }

    /**
     * Register translation filter
     */
    private function registerTranslationFilter(Template $engine, Context $context): void
    {
        $engine->registerFilter('t', function ($input, $params = []) use ($context) {
            return $this->translationService->translate($input, $params, $context);
        });
    }

    /**
     * Register filters related to theme settings
     */
    private function registerSettingsFilters(Template $engine, Context $context): void
    {
        // Filter asset_img_url - returns an img tag for an asset
        $engine->registerFilter('img_url', function($assetName, $size = '100x') use ($context) {
            if (empty($assetName)) {
                return '';
            }
            
            $theme = $context->get('theme');
            $storeId = $theme['store_id'];
            $themeId = $theme['id'];
            
            return url("assets/{$storeId}/{$themeId}/{$assetName}");
        });
    }
}