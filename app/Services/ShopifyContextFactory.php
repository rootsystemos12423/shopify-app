<?php

namespace App\Services;

use Illuminate\Http\Request;
use App\Models\Theme;
use Liquid\Context;
use Illuminate\Support\Facades\Log;
use App\Liquid\CustomFilters;

class ShopifyContextFactory
{
    protected array $providers = [];
    
    public function __construct()
    {
        $this->registerProviders();
    }
    
    /**
     * Registra todos os provedores de contexto
     */
    private function registerProviders(): void
    {
        $this->providers = [
            new GlobalObjectsProvider(),
            new ShopProvider(),
            new ThemeProvider(),
            new RequestProvider(),
            new TemplateProvider(),
            new CartProvider(),
            new ProductProvider(),
            new CollectionProvider(),
            new CustomerProvider(),
            new PageProvider(),
            new BlogProvider(),
            new ArticleProvider(),
            new SearchProvider(),
            new CheckoutProvider(),
            new AddressProvider(),
            new FilterProvider(),
            new FormProvider(),
            new OrderProvider(),
            new PaginationProvider(),
            new LocalizationProvider(),
            new MediaProvider(),
            new MetafieldProvider(),
            new ImageProvider(),
            new SellingPlanProvider(),
            new LinksProvider(),
            new SectionProvider(),
        ];
    }
    
    /**
     * Carrega todo o contexto Shopify para o Liquid
     */
    public function loadContext(Context $context, Theme $theme, Request $request): void
    {
        $params = [
            'theme' => $theme,
            'request' => $request,
            'context' => $context,
            'template' => $this->resolveTemplateName($request->path())
        ];
        
        // Registrar os filtros personalizados antes de carregar os provedores de contexto
        $customFilters = new CustomFilters($context);
        
        // Adicionar cada filtro individualmente
        $context->addFilters('asset_url', [$customFilters, 'asset_url']);
        $context->addFilters('stylesheet_tag', [$customFilters, 'stylesheet_tag']);
        $context->addFilters('inline_asset_content', [$customFilters, 'inline_asset_content']);
        $context->addFilters('safe_svg', [$customFilters, 'safe_svg']);
        $context->addFilters('to_string', [$customFilters, 'to_string']);
        $context->addFilters('safe_divide', [$customFilters, 'safe_divide']);
        
        foreach ($this->providers as $provider) {
            try {
                $providerName = get_class($provider);
                
                // Apply the provider
                $provider->provide($context, $params);
                
            } catch (\Exception $e) {
                Log::error("Error in provider {$providerName}: {$e->getMessage()}");
            }
        }
    }
    
    /**
     * Resolve o nome do template baseado no caminho
     */
    private function resolveTemplateName(string $path): string
    {
        $path = trim($path, '/');
        
        $mappings = [
            '' => 'index',
            'products' => 'list-collections',
            'products/([^/]+)' => 'product',
            'collections' => 'list-collections',
            'collections/([^/]+)' => 'collection',
            'cart' => 'cart',
            'pages/([^/]+)' => 'page',
            'blogs/([^/]+)' => 'blog',
            'blogs/([^/]+)/([^/]+)' => 'article',
            'search' => 'search',
            'account' => 'account',
            'account/login' => 'customers/login',
            'account/register' => 'customers/register'
        ];
        
        foreach ($mappings as $pattern => $template) {
            if (empty($pattern) && empty($path)) {
                return $template;
            }
            
            if (preg_match("#^{$pattern}$#", $path)) {
                return $template;
            }
        }
        
        return 'index';
    }
}