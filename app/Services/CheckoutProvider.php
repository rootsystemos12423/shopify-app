<?php

namespace App\Services;

use Liquid\Context;

class CheckoutProvider implements ContextProvider
{
    public function provide(Context $context, array $params): void
    {
        // Adicionar dados de checkout
        $context->set('checkout', $this->getCheckoutData());
    }
    
    private function getCheckoutData(): array
    {
        return [
            'id' => 987654321,
            'attributes' => [
                'note' => 'Entregar em horário comercial.'
            ],
            'billing_address' => [
                'first_name' => 'João',
                'last_name' => 'Silva',
                'address1' => 'Rua Exemplo, 123',
                'address2' => 'Apto 45',
                'city' => 'São Paulo',
                'province' => 'São Paulo',
                'province_code' => 'SP',
                'country' => 'Brasil',
                'country_code' => 'BR',
                'zip' => '01234-567',
                'phone' => '(11) 99999-9999',
                'company' => 'Empresa Exemplo',
                'name' => 'João Silva',
                'street' => 'Rua Exemplo, 123, Apto 45'
            ],
            'shipping_address' => [
                'first_name' => 'João',
                'last_name' => 'Silva',
                'address1' => 'Rua Exemplo, 123',
                'address2' => 'Apto 45',
                'city' => 'São Paulo',
                'province' => 'São Paulo',
                'province_code' => 'SP',
                'country' => 'Brasil',
                'country_code' => 'BR',
                'zip' => '01234-567',
                'phone' => '(11) 99999-9999',
                'company' => 'Empresa Exemplo',
                'name' => 'João Silva',
                'street' => 'Rua Exemplo, 123, Apto 45'
            ],
            'email' => 'joao.silva@example.com',
            'currency' => 'BRL',
            'requires_shipping' => true,
            'shipping_method' => [
                'handle' => 'sedex',
                'title' => 'SEDEX',
                'price' => 1500,
                'original_price' => 1500,
                'trc_tracking' => true,
                'code' => 'SEDEX'
            ],
            'shipping_methods' => [
                [
                    'handle' => 'sedex',
                    'title' => 'SEDEX',
                    'price' => 1500,
                    'original_price' => 1500,
                    'trc_tracking' => true,
                    'code' => 'SEDEX'
                ],
                [
                    'handle' => 'pac',
                    'title' => 'PAC',
                    'price' => 1000,
                    'original_price' => 1000,
                    'trc_tracking' => true,
                    'code' => 'PAC'
                ]
            ],
            'shipping_price' => 1500,
            'tax_lines' => [],
            'tax_price' => 0,
            'discount_applications' => [],
            'discounts' => [],
            'gift_cards' => [],
            'line_items' => [
                [
                    'id' => 1111,
                    'key' => 'f1d2b3c4',
                    'title' => 'Camiseta Básica',
                    'quantity' => 2,
                    'variant_id' => 12345,
                    'variant_title' => 'Branco / P',
                    'variant' => [
                        'id' => 12345,
                        'title' => 'Branco / P',
                        'price' => 4990,
                        'compare_at_price' => 5990
                    ],
                    'product_id' => 1234567890,
                    'product' => [
                        'id' => 1234567890,
                        'title' => 'Camiseta Básica',
                        'handle' => 'camiseta-basica',
                        'vendor' => 'Marca Demo',
                        'type' => 'Camiseta'
                    ],
                    'price' => 4990,
                    'line_price' => 9980,
                    'final_price' => 9980,
                    'final_line_price' => 9980,
                    'sku' => 'CB-BR-P-001',
                    'grams' => 200,
                    'vendor' => 'Marca Demo',
                    'taxable' => true,
                    'image' => 'https://cdn.shopify.com/s/files/1/0000/0000/0000/products/camiseta-basica.jpg',
                    'url' => '/products/camiseta-basica?variant=12345',
                    'requires_shipping' => true,
                    'gift_card' => false
                ],
                [
                    'id' => 2222,
                    'key' => 'e5f6g7h8',
                    'title' => 'Calça Jeans',
                    'quantity' => 1,
                    'variant_id' => 23456,
                    'variant_title' => 'Azul / 40',
                    'variant' => [
                        'id' => 23456,
                        'title' => 'Azul / 40',
                        'price' => 14990,
                        'compare_at_price' => 19990
                    ],
                    'product_id' => 2345678901,
                    'product' => [
                        'id' => 2345678901,
                        'title' => 'Calça Jeans',
                        'handle' => 'calca-jeans',
                        'vendor' => 'Marca Demo',
                        'type' => 'Calça'
                    ],
                    'price' => 14990,
                    'line_price' => 14990,
                    'final_price' => 14990,
                    'final_line_price' => 14990,
                    'sku' => 'CJ-AZ-40-001',
                    'grams' => 600,
                    'vendor' => 'Marca Demo',
                    'taxable' => true,
                    'image' => 'https://cdn.shopify.com/s/files/1/0000/0000/0000/products/calca-jeans.jpg',
                    'url' => '/products/calca-jeans?variant=23456',
                    'requires_shipping' => true,
                    'gift_card' => false
                ]
            ],
            'order_id' => null,
            'order_name' => null,
            'order_number' => null,
            'subtotal_price' => 24970,
            'total_price' => 26470,
            'total_tax' => 0,
            'payment_due' => 26470,
            'completed_at' => null,
            'created_at' => date('Y-m-d\TH:i:s', strtotime('-30 minutes')),
            'updated_at' => date('Y-m-d\TH:i:s', strtotime('-5 minutes')),
            'customer' => [
                'id' => 9876543210,
                'email' => 'joao.silva@example.com',
                'name' => 'João Silva',
                'first_name' => 'João',
                'last_name' => 'Silva',
                'accepts_marketing' => true
            ],
            'discount' => null,
            'discounts_amount' => 0,
            'discounts_savings' => 0,
            'gift_cards_amount' => 0,
            'tax_included' => false,
            'taxes_included' => false,
            'requires_billing_address' => true,
            'applied_discount' => null,
            'applied_discounts' => [],
            'source_name' => 'web',
            'source_identifier' => null,
            'shipping_method_required' => true,
            'shipping_rate' => [
                'handle' => 'sedex',
                'title' => 'SEDEX',
                'price' => 1500,
                'original_price' => 1500,
                'trc_tracking' => true,
                'code' => 'SEDEX'
            ],
            'shipping_rates' => [
                [
                    'handle' => 'sedex',
                    'title' => 'SEDEX',
                    'price' => 1500,
                    'original_price' => 1500,
                    'trc_tracking' => true,
                    'code' => 'SEDEX'
                ],
                [
                    'handle' => 'pac',
                    'title' => 'PAC',
                    'price' => 1000,
                    'original_price' => 1000,
                    'trc_tracking' => true,
                    'code' => 'PAC'
                ]
            ],
            'credit_card' => null,
            'gateway' => null,
            'payment_methods' => [
                [
                    'id' => 'credit_card',
                    'name' => 'Cartão de Crédito'
                ],
                [
                    'id' => 'boleto',
                    'name' => 'Boleto Bancário'
                ],
                [
                    'id' => 'pix',
                    'name' => 'PIX'
                ]
            ],
            'transactions' => [],
            'gift_cards_enabled' => true,
            'line_items_count' => 2,
            'checkout_page' => [
                'step' => 'contact_information', // Possíveis valores: contact_information, shipping_method, payment_method, review
                'progress' => 25
            ]
        ];
    }
}