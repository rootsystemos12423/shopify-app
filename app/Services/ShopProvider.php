<?php

namespace App\Services;

use Liquid\Context;
use Illuminate\Support\Facades\Cache;

class ShopProvider implements ContextProvider
{
    const CACHE_DURATION = 60; // minutos
    
    public function provide(Context $context, array $params): void
    {
        $theme = $params['theme'] ?? null;
        
        if (!$theme) {
            return;
        }
        
        $shopData = $this->getShopData($theme);
        $context->set('shop', $shopData);
    }
    
    private function getShopData($theme): array
    {
        $cacheKey = "shop_data_{$theme->store_id}";
        
        return Cache::remember($cacheKey, self::CACHE_DURATION, function() use ($theme) {
            return [
                'id' => $theme->store_id,
                'name' => $theme->store->name ?? 'Demo Store',
                'email' => $theme->store->email ?? 'store@example.com',
                'url' => 'https://' . ($theme->store->domain ?? 'example.myshopify.com'),
                'secure_url' => 'https://' . ($theme->store->domain ?? 'example.myshopify.com'),
                'permanent_domain' => ($theme->store->domain ?? 'example.myshopify.com'),
                'domain' => ($theme->store->domain ?? 'example.myshopify.com'),
                'locale' => 'pt-BR',
                'currency' => 'BRL',
                'money_format' => 'R$ {{amount}}',
                'money_with_currency_format' => 'R$ {{amount}} BRL',
                'address' => $this->getAddress($theme),
                'description' => $theme->store->description ?? 'Uma loja de exemplo',
                'phone' => $theme->store->phone ?? '(11) 99999-9999',
                'customer_accounts_enabled' => true,
                'customer_accounts_optional' => false,
                'enabled_payment_types' => [
                    'american_express',
                    'boleto',
                    'discover',
                    'mastercard',
                    'visa'
                ],
                'enables_local_delivery' => true,
                'enables_local_pickup' => true,
                'metafields' => [],
                'password_message' => '',
                'policies' => $this->getPolicies(),
                'published_locales' => [
                    ['iso_code' => 'pt-BR', 'name' => 'Português (Brasil)', 'endonym_name' => 'Português (Brasil)']
                ],
                'brand' => [
                    'logo' => [
                        'src' => 'https://cdn.shopify.com/s/files/1/0000/0000/0000/files/logo.png',
                        'width' => 200,
                        'height' => 60,
                        'alt' => 'Logo da Loja'
                    ],
                    'colors' => [
                        'primary' => [
                            'background' => '#000000',
                            'foreground' => '#FFFFFF'
                        ],
                        'secondary' => [
                            'background' => '#111111',
                            'foreground' => '#EEEEEE'
                        ]
                    ],
                    'short_description' => 'Uma breve descrição da marca',
                    'cover_image' => [
                        'src' => 'https://cdn.shopify.com/s/files/1/0000/0000/0000/files/cover.jpg',
                        'width' => 1200,
                        'height' => 400,
                        'alt' => 'Imagem de capa da loja'
                    ],
                ],
                'cart_terms_required' => false,
                'favicon_url' => 'https://cdn.shopify.com/s/files/1/0000/0000/0000/files/favicon.ico',
                'privacy_policy' => [
                    'title' => 'Política de Privacidade',
                    'url' => '/policies/privacy-policy',
                    'body' => 'Exemplo de política de privacidade'
                ],
                'refund_policy' => [
                    'title' => 'Política de Reembolso',
                    'url' => '/policies/refund-policy',
                    'body' => 'Exemplo de política de reembolso'
                ],
                'shipping_policy' => [
                    'title' => 'Política de Envio',
                    'url' => '/policies/shipping-policy',
                    'body' => 'Exemplo de política de envio'
                ],
                'subscription_policy' => [
                    'title' => 'Política de Assinatura',
                    'url' => '/policies/subscription-policy',
                    'body' => 'Exemplo de política de assinatura'
                ],
                'terms_of_service' => [
                    'title' => 'Termos de Serviço',
                    'url' => '/policies/terms-of-service',
                    'body' => 'Exemplo de termos de serviço'
                ],
                'legal_notice' => [
                    'title' => 'Aviso Legal',
                    'url' => '/policies/legal-notice',
                    'body' => 'Exemplo de aviso legal'
                ]
            ];
        });
    }
    
    private function getAddress($theme): array
    {
        return [
            'address1' => $theme->store->address1 ?? 'Rua Exemplo, 123',
            'address2' => $theme->store->address2 ?? 'Sala 45',
            'city' => $theme->store->city ?? 'São Paulo',
            'company' => $theme->store->company ?? 'Empresa Demo',
            'country' => 'Brasil',
            'country_code' => 'BR',
            'province' => 'São Paulo',
            'province_code' => 'SP',
            'zip' => $theme->store->zip ?? '01234-567',
            'phone' => $theme->store->phone ?? '(11) 99999-9999',
            'latitude' => -23.550520,
            'longitude' => -46.633308,
            'summary' => 'Rua Exemplo, 123, São Paulo, SP, 01234-567, Brasil'
        ];
    }
    
    private function getPolicies(): array
    {
        return [
            [
                'title' => 'Política de Privacidade',
                'url' => '/policies/privacy-policy',
                'body' => 'Exemplo de política de privacidade'
            ],
            [
                'title' => 'Política de Reembolso',
                'url' => '/policies/refund-policy',
                'body' => 'Exemplo de política de reembolso'
            ],
            [
                'title' => 'Política de Envio',
                'url' => '/policies/shipping-policy',
                'body' => 'Exemplo de política de envio'
            ],
            [
                'title' => 'Termos de Serviço',
                'url' => '/policies/terms-of-service',
                'body' => 'Exemplo de termos de serviço'
            ]
        ];
    }
}