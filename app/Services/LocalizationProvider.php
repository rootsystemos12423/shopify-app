<?php

namespace App\Services;

use Liquid\Context;

class LocalizationProvider implements ContextProvider
{
    public function provide(Context $context, array $params): void
    {
        $context->set('localization', $this->getLocalizationData());
        $context->set('locale', 'pt-BR');
        $context->set('language', $this->getLanguageData());
    }
    
    private function getLocalizationData(): array
    {
        return [
            'available_countries' => [
                [
                    'name' => 'Brasil',
                    'iso_code' => 'BR',
                    'currency' => [
                        'iso_code' => 'BRL',
                        'symbol' => 'R$',
                        'format' => 'R$ {{amount_with_comma_separator}}',
                        'decimal_separator' => ',',
                        'thousands_separator' => '.',
                        'decimal_places' => 2
                    ]
                ],
                [
                    'name' => 'Estados Unidos',
                    'iso_code' => 'US',
                    'currency' => [
                        'iso_code' => 'USD',
                        'symbol' => '$',
                        'format' => '${{amount}}',
                        'decimal_separator' => '.',
                        'thousands_separator' => ',',
                        'decimal_places' => 2
                    ]
                ],
                [
                    'name' => 'Argentina',
                    'iso_code' => 'AR',
                    'currency' => [
                        'iso_code' => 'ARS',
                        'symbol' => '$',
                        'format' => '${{amount_with_comma_separator}}',
                        'decimal_separator' => ',',
                        'thousands_separator' => '.',
                        'decimal_places' => 2
                    ]
                ]
            ],
            'available_currencies' => [
                [
                    'iso_code' => 'BRL',
                    'name' => 'Real Brasileiro',
                    'symbol' => 'R$',
                    'format' => 'R$ {{amount_with_comma_separator}}',
                    'decimal_separator' => ',',
                    'thousands_separator' => '.',
                    'decimal_places' => 2
                ],
                [
                    'iso_code' => 'USD',
                    'name' => 'Dólar Americano',
                    'symbol' => '$',
                    'format' => '${{amount}}',
                    'decimal_separator' => '.',
                    'thousands_separator' => ',',
                    'decimal_places' => 2
                ],
                [
                    'iso_code' => 'EUR',
                    'name' => 'Euro',
                    'symbol' => '€',
                    'format' => '€{{amount_with_comma_separator}}',
                    'decimal_separator' => ',',
                    'thousands_separator' => '.',
                    'decimal_places' => 2
                ]
            ],
            'available_languages' => [
                [
                    'iso_code' => 'pt-BR',
                    'name' => 'Português (Brasil)',
                    'endonym_name' => 'Português (Brasil)',
                    'root_url' => '/',
                    'primary' => true
                ],
                [
                    'iso_code' => 'en',
                    'name' => 'Inglês',
                    'endonym_name' => 'English',
                    'root_url' => '/en',
                    'primary' => false
                ],
                [
                    'iso_code' => 'es',
                    'name' => 'Espanhol',
                    'endonym_name' => 'Español',
                    'root_url' => '/es',
                    'primary' => false
                ]
            ],
            'available_locales' => [
                [
                    'iso_code' => 'pt-BR',
                    'name' => 'Português (Brasil)',
                    'endonym_name' => 'Português (Brasil)',
                    'root_url' => '/',
                    'primary' => true
                ],
                [
                    'iso_code' => 'en',
                    'name' => 'Inglês',
                    'endonym_name' => 'English',
                    'root_url' => '/en',
                    'primary' => false
                ],
                [
                    'iso_code' => 'es',
                    'name' => 'Espanhol',
                    'endonym_name' => 'Español',
                    'root_url' => '/es',
                    'primary' => false
                ]
            ],
            'country' => [
                'name' => 'Brasil',
                'iso_code' => 'BR',
                'currency' => [
                    'iso_code' => 'BRL',
                    'symbol' => 'R$',
                    'format' => 'R$ {{amount_with_comma_separator}}',
                    'decimal_separator' => ',',
                    'thousands_separator' => '.',
                    'decimal_places' => 2
                ]
            ],
            'currency' => [
                'iso_code' => 'BRL',
                'name' => 'Real Brasileiro',
                'symbol' => 'R$',
                'format' => 'R$ {{amount_with_comma_separator}}',
                'decimal_separator' => ',',
                'thousands_separator' => '.',
                'decimal_places' => 2
            ],
            'language' => [
                'iso_code' => 'pt-BR',
                'name' => 'Português (Brasil)',
                'endonym_name' => 'Português (Brasil)',
                'root_url' => '/',
                'primary' => true
            ]
        ];
    }
    
    private function getLanguageData(): array
    {
        return [
            'iso_code' => 'pt-BR',
            'name' => 'Português (Brasil)',
            'endonym_name' => 'Português (Brasil)',
            'root_url' => '/',
            'primary' => true,
            'direction' => 'ltr',
            'translations' => [
                'general' => [
                    'search' => 'Buscar',
                    'cart' => 'Carrinho',
                    'menu' => 'Menu',
                    'close' => 'Fechar',
                    'loading' => 'Carregando',
                    'continue_shopping' => 'Continuar comprando',
                    'home' => 'Início',
                    'account' => 'Conta',
                    'login' => 'Entrar',
                    'logout' => 'Sair',
                    'register' => 'Cadastrar',
                    'addresses' => 'Endereços',
                    'orders' => 'Pedidos',
                    'submit' => 'Enviar',
                    'cancel' => 'Cancelar',
                    'save' => 'Salvar',
                    'edit' => 'Editar',
                    'delete' => 'Excluir'
                ],
                'products' => [
                    'product' => 'Produto',
                    'add_to_cart' => 'Adicionar ao carrinho',
                    'sold_out' => 'Esgotado',
                    'unavailable' => 'Indisponível',
                    'on_sale' => 'Promoção',
                    'price' => 'Preço',
                    'compare_at_price' => 'De',
                    'quantity' => 'Quantidade',
                    'view_product' => 'Ver produto',
                    'related_products' => 'Produtos relacionados',
                    'description' => 'Descrição',
                    'specifications' => 'Especificações',
                    'reviews' => 'Avaliações',
                    'sku' => 'SKU',
                    'vendor' => 'Fornecedor',
                    'type' => 'Tipo',
                    'color' => 'Cor',
                    'size' => 'Tamanho'
                ],
                'cart' => [
                    'title' => 'Carrinho',
                    'empty' => 'Seu carrinho está vazio',
                    'subtotal' => 'Subtotal',
                    'shipping' => 'Frete',
                    'tax' => 'Impostos',
                    'total' => 'Total',
                    'checkout' => 'Finalizar compra',
                    'update' => 'Atualizar',
                    'remove' => 'Remover',
                    'note' => 'Observações do pedido',
                    'discount' => 'Desconto',
                    'apply_discount' => 'Aplicar cupom'
                ],
                'checkout' => [
                    'title' => 'Finalizar compra',
                    'contact_information' => 'Informações de contato',
                    'shipping_address' => 'Endereço de entrega',
                    'billing_address' => 'Endereço de cobrança',
                    'shipping_method' => 'Método de envio',
                    'payment_method' => 'Método de pagamento',
                    'review' => 'Revisar pedido',
                    'place_order' => 'Finalizar pedido',
                    'terms_and_conditions' => 'Termos e condições',
                    'privacy_policy' => 'Política de privacidade'
                ]
            ]
        ];
    }
}