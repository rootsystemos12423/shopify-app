<?php

namespace App\Services;

use Liquid\Context;

class CustomerProvider implements ContextProvider
{
    public function provide(Context $context, array $params): void
    {
        // Em uma aplicação real, aqui verificaríamos se o cliente está autenticado
        // e carregaríamos os dados reais do cliente. Para demonstração, definimos como null.
        $context->set('customer', null);
        
        // Para templates de conta, podemos definir um cliente de exemplo
        if (in_array($params['template'], ['account', 'customers/login', 'customers/register'])) {
            $context->set('customer', $this->getCustomerData());
        }
    }
    
    private function getCustomerData(): array
    {
        return [
            'id' => 9876543210,
            'accepts_marketing' => true,
            'accepts_marketing_updated_at' => date('Y-m-d\TH:i:s', strtotime('-1 month')),
            'marketing_opt_in_level' => 'single_opt_in',
            'first_name' => 'João',
            'last_name' => 'Silva',
            'email' => 'joao.silva@example.com',
            'phone' => '+5511999999999',
            'tags' => ['vip', 'retorno'],
            'tax_exempt' => false,
            'orders_count' => 3,
            'total_spent' => 60000,
            'last_order' => [
                'id' => 1001,
                'name' => '#1001',
                'customer_url' => '/account/orders/1001',
                'created_at' => date('Y-m-d\TH:i:s', strtotime('-1 week')),
                'financial_status' => 'paid',
                'fulfillment_status' => 'fulfilled',
                'total_price' => 25000
            ],
            'has_account' => true,
            'name' => 'João Silva',
            'addresses' => [
                [
                    'id' => 101,
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
                    'phone' => '11999999999',
                    'company' => 'Empresa Exemplo',
                    'name' => 'João Silva',
                    'default' => true,
                    'street' => 'Rua Exemplo, 123, Apto 45'
                ],
                [
                    'id' => 102,
                    'first_name' => 'João',
                    'last_name' => 'Silva',
                    'address1' => 'Rua Comercial, 456',
                    'address2' => 'Sala 78',
                    'city' => 'São Paulo',
                    'province' => 'São Paulo',
                    'province_code' => 'SP',
                    'country' => 'Brasil',
                    'country_code' => 'BR',
                    'zip' => '04567-890',
                    'phone' => '11999999999',
                    'company' => 'Trabalho Exemplo',
                    'name' => 'João Silva',
                    'default' => false,
                    'street' => 'Rua Comercial, 456, Sala 78'
                ]
            ],
            'default_address' => [
                'id' => 101,
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
                'phone' => '11999999999',
                'company' => 'Empresa Exemplo',
                'name' => 'João Silva',
                'default' => true,
                'street' => 'Rua Exemplo, 123, Apto 45'
            ],
            'orders' => [
                [
                    'id' => 1001,
                    'name' => '#1001',
                    'customer_url' => '/account/orders/1001',
                    'order_number' => 1001,
                    'created_at' => date('Y-m-d\TH:i:s', strtotime('-1 week')),
                    'financial_status' => 'paid',
                    'fulfillment_status' => 'fulfilled',
                    'processed_at' => date('Y-m-d\TH:i:s', strtotime('-1 week')),
                    'total_price' => 25000,
                    'subtotal_price' => 23000,
                    'total_shipping_price' => 2000,
                    'total_tax' => 0
                ],
                [
                    'id' => 991,
                    'name' => '#991',
                    'customer_url' => '/account/orders/991',
                    'order_number' => 991,
                    'created_at' => date('Y-m-d\TH:i:s', strtotime('-2 months')),
                    'financial_status' => 'paid',
                    'fulfillment_status' => 'fulfilled',
                    'processed_at' => date('Y-m-d\TH:i:s', strtotime('-2 months')),
                    'total_price' => 18000,
                    'subtotal_price' => 18000,
                    'total_shipping_price' => 0,
                    'total_tax' => 0
                ]
            ]
        ];
    }
}