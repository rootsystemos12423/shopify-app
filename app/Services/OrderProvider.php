<?php

namespace App\Services;

use Liquid\Context;

class OrderProvider implements ContextProvider
{
    public function provide(Context $context, array $params): void
    {
        // Adicionar objeto de pedido se estamos em uma página de conta
        if (in_array($params['template'], ['customers/order', 'order_confirmation'])) {
            $context->set('order', $this->getOrderData());
        }
    }
    
    private function getOrderData(): array
    {
        return [
            'id' => 1001,
            'name' => '#1001',
            'order_number' => 1001,
            'customer_url' => '/account/orders/1001',
            'order_confirmation_url' => '/checkout/orders/1001/confirmation',
            'created_at' => date('Y-m-d\TH:i:s', strtotime('-1 week')),
            'processed_at' => date('Y-m-d\TH:i:s', strtotime('-1 week')),
            'cancelled_at' => null,
            'cancel_reason' => null,
            'cancelled' => false,
            'financial_status' => 'paid',
            'fulfillment_status' => 'fulfilled',
            'customer' => [
                'id' => 9876543210,
                'email' => 'joao.silva@example.com',
                'name' => 'João Silva',
                'first_name' => 'João',
                'last_name' => 'Silva',
                'accepts_marketing' => true
            ],
            'customer_id' => 9876543210,
            'discount_applications' => [],
            'discounts' => [],
            'tags' => [],
            'tax_lines' => [],
            'tax_price' => 0,
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
            'shipping_methods' => [
                [
                    'handle' => 'sedex',
                    'title' => 'SEDEX',
                    'price' => 1500,
                    'original_price' => 1500,
                    'trc_tracking' => true,
                    'code' => 'SEDEX'
                ]
            ],
            'shipping_price' => 1500,
            'transactions' => [
                [
                    'id' => 'txn_123456',
                    'amount' => 26470,
                    'kind' => 'sale',
                    'status' => 'success',
                    'created_at' => date('Y-m-d\TH:i:s', strtotime('-1 week')),
                    'gateway' => 'credit_card',
                    'payment_details' => [
                        'credit_card_company' => 'visa',
                        'credit_card_last_four_digits' => '4242',
                        'credit_card_number' => '•••• •••• •••• 4242',
                        'credit_card_wallet' => null
                    ]
                ]
            ],
            'payment_details' => [
                'credit_card_company' => 'visa',
                'credit_card_last_four_digits' => '4242',
                'credit_card_number' => '•••• •••• •••• 4242',
                'credit_card_wallet' => null
            ],
            'fulfillments' => [
                [
                    'id' => 'fulfillment_12345',
                    'created_at' => date('Y-m-d\TH:i:s', strtotime('-6 days')),
                    'updated_at' => date('Y-m-d\TH:i:s', strtotime('-6 days')),
                    'tracking_company' => 'Correios',
                    'tracking_number' => 'BR1234567890XX',
                    'tracking_url' => 'https://www.correios.com.br/rastreamento?numero=BR1234567890XX'
                ]
            ],
            'line_items' => [
                [
                    'id' => 1111,
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
                    'gift_card' => false,
                    'properties' => [],
                    'fulfillment' => [
                        'tracking_number' => 'BR1234567890XX',
                        'tracking_url' => 'https://www.correios.com.br/rastreamento?numero=BR1234567890XX',
                        'tracking_company' => 'Correios'
                    ]
                ],
                [
                    'id' => 2222,
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
                    'gift_card' => false,
                    'properties' => [],
                    'fulfillment' => [
                        'tracking_number' => 'BR1234567890XX',
                        'tracking_url' => 'https://www.correios.com.br/rastreamento?numero=BR1234567890XX',
                        'tracking_company' => 'Correios'
                    ]
                ]
            ],
            'subtotal_price' => 24970,
            'total_price' => 26470,
            'total_tax' => 0,
            'total_discounts' => 0,
            'total_weight' => 1000,
            'total_items' => 3,
            'total_line_items_price' => 24970,
            'item_count' => 2,
            'currency' => 'BRL',
            'has_shipping_address' => true,
            'requires_shipping' => true,
            'note' => 'Entregar em horário comercial.',
            'confirmed' => true,
            'checkout_id' => 'chk_12345',
            'email' => 'joao.silva@example.com'
        ];
    }
}