<?php

namespace App\Services;

use Liquid\Context;

class MetafieldProvider implements ContextProvider
{
    public function provide(Context $context, array $params): void
    {
        // Adicionar metafields para vários objetos
        $context->set('metafield', $this->getDummyMetafield());
        
        // Adicionar metafields para produto se estamos em uma página de produto
        if ($params['template'] === 'product') {
            // Obter produto atual
            $product = $context->get('product');
            if (is_array($product)) {
                $product['metafields'] = $this->getProductMetafields();
                $context->set('product', $product);
            } else {
                // Se produto ainda não existe no contexto, criar com metafields
                $context->set('product', [
                    'metafields' => $this->getProductMetafields()
                ]);
            }
        }
        
        // Adicionar metafields para loja
        $shop = $context->get('shop');
        if (is_array($shop)) {
            $shop['metafields'] = $this->getShopMetafields();
            $context->set('shop', $shop);
        }
    }
    
    private function getDummyMetafield(): array
    {
        return [
            'key' => 'key',
            'namespace' => 'namespace',
            'value' => 'value',
            'type' => 'string',
            'owner_id' => null,
            'owner_resource' => null,
            'owner_type' => null,
            'description' => null
        ];
    }
    
    private function getProductMetafields(): array
    {
        return [
            'specifications' => [
                'material' => [
                    'type' => 'string',
                    'value' => '100% Algodão'
                ],
                'modelo' => [
                    'type' => 'string',
                    'value' => 'Regular fit'
                ],
                'cuidados' => [
                    'type' => 'string',
                    'value' => 'Lavar em máquina a 30°C. Não usar alvejante. Não limpar a seco. Passar ferro a temperatura baixa.'
                ],
                'origem' => [
                    'type' => 'string',
                    'value' => 'Brasil'
                ]
            ],
            'seo' => [
                'title' => [
                    'type' => 'string',
                    'value' => 'Camiseta Básica de Alta Qualidade - 100% Algodão | Loja Demo'
                ],
                'description' => [
                    'type' => 'string',
                    'value' => 'Camiseta básica 100% algodão, ideal para o dia a dia. Disponível em várias cores e tamanhos. Entrega rápida e frete grátis acima de R$ 99,90.'
                ],
                'keywords' => [
                    'type' => 'string',
                    'value' => 'camiseta básica, camiseta algodão, camiseta confortável, camiseta casual'
                ]
            ],
            'reviews' => [
                'rating' => [
                    'type' => 'number_decimal',
                    'value' => 4.8
                ],
                'rating_count' => [
                    'type' => 'number_integer',
                    'value' => 24
                ]
            ]
        ];
    }
    
    private function getShopMetafields(): array
    {
        return [
            'social' => [
                'facebook' => [
                    'type' => 'string',
                    'value' => 'https://facebook.com/lojademo'
                ],
                'instagram' => [
                    'type' => 'string',
                    'value' => 'https://instagram.com/lojademo'
                ],
                'youtube' => [
                    'type' => 'string',
                    'value' => 'https://youtube.com/lojademo'
                ],
                'twitter' => [
                    'type' => 'string',
                    'value' => 'https://twitter.com/lojademo'
                ]
            ],
            'contact' => [
                'phone' => [
                    'type' => 'string',
                    'value' => '(11) 9999-9999'
                ],
                'email' => [
                    'type' => 'string',
                    'value' => 'contato@lojademo.com.br'
                ],
                'whatsapp' => [
                    'type' => 'string',
                    'value' => '(11) 99999-9999'
                ],
                'address' => [
                    'type' => 'string',
                    'value' => 'Rua Exemplo, 123 - São Paulo/SP'
                ]
            ],
            'shipping' => [
                'free_shipping_minimum' => [
                    'type' => 'number_decimal',
                    'value' => 9990
                ],
                'free_shipping_text' => [
                    'type' => 'string',
                    'value' => 'Frete grátis para compras acima de R$ 99,90'
                ]
            ]
        ];
    }
}