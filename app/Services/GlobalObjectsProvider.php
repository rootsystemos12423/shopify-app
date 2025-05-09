<?php

namespace App\Services;

use Liquid\Context;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class GlobalObjectsProvider implements ContextProvider
{
    /**
     * @var TranslationService|null
     */
    protected $translationService;
    
    /**
     * @var ThemeSettingsService|null
     */
    protected $themeSettingsService;
    
    /**
     * Constructor with optional dependencies
     */
    public function __construct(
        TranslationService $translationService = null,
        ThemeSettingsService $themeSettingsService = null
    ) {
        $this->translationService = $translationService;
        $this->themeSettingsService = $themeSettingsService;
    }
    
    /**
     * Provide global objects to the Liquid context
     */
    public function provide(Context $context, array $params): void
    {
        // Extract key parameters
        $request = $params['request'] ?? request();
        $theme = $params['theme'] ?? null;
        $templateName = $params['template'] ?? $this->resolveTemplateName($request->path());
        
        try {
            // Add global objects that are always available in all templates
            $globals = [
                'all_country_option_tags' => $this->getAllCountryOptionTags(),
                'canonical_url' => $this->getCanonicalUrl($request),
                'locale' => $params['locale'] ?? 'pt-BR',
                'handle' => $this->getHandle($request),
                'page_description' => $this->getPageDescription($params),
                'page_title' => $this->getPageTitle($params),
                'current_page' => $params['page'] ?? 1,
                'current_tags' => $params['tags'] ?? [],
                'linklists' => $this->getLinklists(),
                'routes' => $this->getRoutes(),
                'template' => $templateName,
                'predictive_search_resources' => $this->getPredictiveSearchResources(),
                'additional_checkout_buttons' => true,
                'checkout' => $this->getCheckout($params),
                'powered_by_link' => '<a href="https://shopify.com">Powered by Shopify</a>',
                'payment_button' => $this->getPaymentButton(),
                'tax_line_item_allocation' => $this->getTaxLineItemAllocation()
            ];
            
            // Add data to context
            foreach ($globals as $key => $value) {
                $context->set($key, $value);
            }
            
            // Add main object collections
            $this->addCollections($context, $params);
            $this->addRecommendations($context, $params);
            $this->addCustomerData($context, $params);
            
            // Add theme settings if theme is available and ThemeSettingsService is injected
            if ($theme && $this->themeSettingsService) {
                $this->addThemeSettings($context, $theme, $params['locale'] ?? null);
            }
            
            // Register in context for use in custom tags
            $context->registers['global_objects'] = $globals;
            
            // Add current page/request data
            $this->addPageData($context, $request, $params);
    
        } catch (\Exception $e) {
            Log::error('Error providing global objects to context', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Still set minimum required objects to prevent template errors
            $context->set('template', $templateName);
            $context->set('routes', $this->getRoutes());
            $context->registers['global_objects'] = [
                'template' => $templateName
            ];
        }
    }
    
    /**
     * Add theme settings to context
     */
    private function addThemeSettings(Context $context, $theme, ?string $locale): void
    {
        try {
            // Only proceed if we have ThemeSettingsService
            if (!$this->themeSettingsService) {
                return;
            }
            
            // Get theme path
            $storePath = is_object($theme) ? "{$theme->store_id}/{$theme->shopify_theme_id}" : $theme;
            
            // Use ThemeSettingsService to initialize context with settings
            $this->themeSettingsService->initializeContext($context, $storePath, $locale);
            
        } catch (\Exception $e) {
            Log::error('Error adding theme settings to context', [
                'error' => $e->getMessage()
            ]);
            
            // Add empty settings to prevent template errors
            $context->set('settings', []);
            $context->registers['settings'] = [];
        }
    }
    
    private function getAllCountryOptionTags(): string
    {
        return '<option value="BR" selected="selected">Brasil</option>
                <option value="US">Estados Unidos</option>
                <option value="AR">Argentina</option>
                <option value="PT">Portugal</option>';
    }
    
    private function getCanonicalUrl(Request $request): string
    {
        return 'https://' . $request->getHost() . $request->getRequestUri();
    }
    
    private function getHandle(Request $request): string
    {
        $path = trim($request->path(), '/');
        
        if (empty($path)) {
            return 'index';
        }
        
        return str_replace('/', '-', $path);
    }
    
    private function getLinklists(): array
    {
        return [
            'main-menu' => [
                'handle' => 'main-menu',
                'title' => 'Menu Principal',
                'links' => [
                    [
                        'title' => 'Home',
                        'url' => '/',
                        'type' => 'frontpage_link',
                        'active' => true
                    ],
                    [
                        'title' => 'Produtos',
                        'url' => '/collections/all',
                        'type' => 'collections_link',
                        'active' => false,
                        'links' => [
                            [
                                'title' => 'Camisetas',
                                'url' => '/collections/camisetas',
                                'type' => 'collection_link',
                                'active' => false
                            ],
                            [
                                'title' => 'Calças',
                                'url' => '/collections/calcas',
                                'type' => 'collection_link',
                                'active' => false
                            ]
                        ]
                    ],
                    [
                        'title' => 'Sobre',
                        'url' => '/pages/sobre',
                        'type' => 'page_link',
                        'active' => false
                    ],
                    [
                        'title' => 'Contato',
                        'url' => '/pages/contato',
                        'type' => 'page_link',
                        'active' => false
                    ]
                ]
            ],
            'footer' => [
                'handle' => 'footer',
                'title' => 'Rodapé',
                'links' => [
                    [
                        'title' => 'Termos de Serviço',
                        'url' => '/policies/terms-of-service',
                        'type' => 'policy_link',
                        'active' => false
                    ],
                    [
                        'title' => 'Política de Privacidade',
                        'url' => '/policies/privacy-policy',
                        'type' => 'policy_link',
                        'active' => false
                    ],
                    [
                        'title' => 'Política de Reembolso',
                        'url' => '/policies/refund-policy',
                        'type' => 'policy_link',
                        'active' => false
                    ]
                ]
            ]
        ];
    }
    
    private function getRoutes(): array
    {
        return [
            'root_url' => '/',
            'account_url' => '/account',
            'account_login_url' => '/account/login',
            'account_logout_url' => '/account/logout',
            'account_register_url' => '/account/register',
            'account_addresses_url' => '/account/addresses',
            'account_orders_url' => '/account/orders',
            'collections_url' => '/collections',
            'all_products_collection_url' => '/collections/all',
            'search_url' => '/search',
            'predictive_search_url' => '/search/suggest',
            'cart_url' => '/cart',
            'cart_add_url' => '/cart/add',
            'cart_change_url' => '/cart/change',
            'cart_update_url' => '/cart/update',
            'cart_clear_url' => '/cart/clear',
            'checkout_url' => '/checkout',
            'products_url' => '/products'
        ];
    }
    
    private function getCollections(): array
    {
        return [
            'all' => [
                'id' => 1,
                'handle' => 'all',
                'title' => 'Todos os Produtos',
                'description' => 'Todos os produtos da loja',
                'url' => '/collections/all',
                'products_count' => 20,
                'all_products_count' => 20,
                'all_types_count' => 5,
                'all_vendors_count' => 10,
                'featured_image' => [
                    'src' => 'https://cdn.shopify.com/s/files/1/0000/0000/0000/collections/all.jpg',
                    'alt' => 'Todos os Produtos'
                ],
                'filters' => [],
                'default_sort_by' => 'manual',
                'sort_by' => 'best-selling',
                'sort_options' => [
                    ['value' => 'manual', 'name' => 'Em destaque'],
                    ['value' => 'best-selling', 'name' => 'Mais vendidos'],
                    ['value' => 'title-ascending', 'name' => 'Ordem alfabética, A-Z'],
                    ['value' => 'title-descending', 'name' => 'Ordem alfabética, Z-A'],
                    ['value' => 'price-ascending', 'name' => 'Preço, menor para o maior'],
                    ['value' => 'price-descending', 'name' => 'Preço, maior para o menor'],
                    ['value' => 'created-ascending', 'name' => 'Data, mais antiga'],
                    ['value' => 'created-descending', 'name' => 'Data, mais recente']
                ],
                'products' => []  // Would be filled with actual products
            ]
        ];
    }
    
    private function getRecommendations(): array
    {
        return [
            'products' => [],  // Would be filled with recommended products
            'performed' => true,
            'product_recommendations' => []
        ];
    }
    
    private function getPredictiveSearchResources(): array
    {
        return [
            'articles' => true,
            'pages' => true,
            'products' => true
        ];
    }
    
    private function getCheckout(array $params = []): array
    {
        // Get existing checkout from params if available
        $checkout = $params['checkout'] ?? [];
        
        // Merge with defaults
        return array_merge([
            'gift_cards_enabled' => true,
            'requires_shipping' => true,
            'shipping_address' => null,
            'shipping_price' => null,
            'tax_price' => null,
            'total_price' => 0,
            'total_tax' => null,
            'currency' => 'BRL',
            'locale' => 'pt-BR'
        ], $checkout);
    }
    
    private function getPaymentButton(): array
    {
        return [
            'accessToken' => 'demo_token_123456789',
            'shopId' => 123456789
        ];
    }
    
    private function getTaxLineItemAllocation(): array
    {
        return [];
    }
    
    private function resolveTemplateName(string $path): string
    {
        $mappings = [
            '/' => 'index', 
            'produtos' => 'collection',
            'produtos/*' => 'product',
            'carrinho' => 'cart',
            'paginas/*' => 'page',
            'colecoes' => 'list-collections',
            'busca' => 'search'
        ];
        
        $path = trim($path, '/');
        
        foreach ($mappings as $pattern => $name) {
            if ($this->wildcardMatch($pattern, $path)) {
                return $name;
            }
        }
        
        // Fallback to index
        return $path ?: 'index';
    }
    
    private function wildcardMatch(string $pattern, string $subject): bool
    {
        // Convert wildcard pattern to regex
        $regex = str_replace(
            ['*', '/'],
            ['.*', '\\/'],
            $pattern
        );
        
        return (bool) preg_match('/^' . $regex . '$/', $subject);
    }
    
    private function getPageTitle(array $params): string
    {
        if (isset($params['page_title'])) {
            return $params['page_title'];
        }
        
        if (isset($params['product'])) {
            return $params['product']['title'] ?? 'Product';
        }
        
        if (isset($params['collection'])) {
            return $params['collection']['title'] ?? 'Collection';
        }
        
        if (isset($params['page'])) {
            return $params['page']['title'] ?? 'Page';
        }
        
        if (isset($params['article'])) {
            return $params['article']['title'] ?? 'Article';
        }
        
        // Default title
        return 'Online Store';
    }
    
    private function getPageDescription(array $params): string
    {
        if (isset($params['page_description'])) {
            return $params['page_description'];
        }
        
        if (isset($params['product']) && isset($params['product']['description'])) {
            return $this->truncateText($params['product']['description'], 160);
        }
        
        if (isset($params['collection']) && isset($params['collection']['description'])) {
            return $this->truncateText($params['collection']['description'], 160);
        }
        
        if (isset($params['page']) && isset($params['page']['content'])) {
            return $this->truncateText($params['page']['content'], 160);
        }
        
        // Default description
        return 'Seu destino para encontrar produtos de qualidade.';
    }
    
    private function truncateText(string $text, int $length): string
    {
        $text = strip_tags($text);
        
        if (strlen($text) <= $length) {
            return $text;
        }
        
        $truncated = substr($text, 0, $length - 3);
        return rtrim($truncated) . '...';
    }
    
    private function addCollections(Context $context, array $params): void
    {
        // Get collections from params or create default "all" collection
        $collections = $params['collections'] ?? $this->getCollections();
        $context->set('collections', $collections);
        
        // If specific collection is active, add it separately
        if (isset($params['collection'])) {
            $context->set('collection', $params['collection']);
        } elseif (isset($collections['all'])) {
            $context->set('collection', $collections['all']);
        }
    }
    
    private function addRecommendations(Context $context, array $params): void
    {
        $recommendations = $params['recommendations'] ?? $this->getRecommendations();
        $context->set('recommendations', $recommendations);
    }
    
    private function addCustomerData(Context $context, array $params): void
    {
        // Set customer data if available
        $customer = $params['customer'] ?? null;
        $context->set('customer', $customer);
        
        // Set customer address if available
        $customerAddress = $params['customer_address'] ?? null;
        $context->set('customer_address', $customerAddress);
    }
    
    private function addPageData(Context $context, Request $request, array $params): void
    {
        // Add current URL and path
        $context->set('current_url', $request->fullUrl());
        $context->set('current_path', $request->path());
        
        // Add page-specific data
        if (isset($params['product'])) {
            $context->set('product', $params['product']);
        }
        
        if (isset($params['page'])) {
            $context->set('page', $params['page']);
        }
        
        if (isset($params['article'])) {
            $context->set('article', $params['article']);
        }
        
        if (isset($params['blog'])) {
            $context->set('blog', $params['blog']);
        }
        
        // Add cart if available
        if (isset($params['cart'])) {
            $context->set('cart', $params['cart']);
        }
    }
}