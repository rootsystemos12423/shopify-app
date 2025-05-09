<?php

namespace App\Services;

use Liquid\Context;

class ProductProvider implements ContextProvider
{
    public function provide(Context $context, array $params): void
    {
        // Adiciona um produto exemplo se estamos em uma página de produto
        if ($params['template'] === 'product') {
            $context->set('product', $this->getProductData());
            $context->set('selected_variant', $this->getProductData()['variants'][0]);
            $context->set('featured_media', $this->getProductData()['media'][0]);
            $context->set('selected_or_first_available_variant', $this->getProductData()['variants'][0]);
            $context->set('first_available_variant', $this->getProductData()['variants'][0]);
        }
        
        // Produtos relacionados
        $context->set('related_products', $this->getRelatedProducts());
        
        // Adiciona collection específica do produto
        $context->set('collection', $this->getProductCollection());
    }
    
    private function getProductData(): array
    {
        return [
            'id' => 1234567890,
            'title' => 'Camiseta Básica',
            'handle' => 'camiseta-basica',
            'description' => '<p>Uma camiseta básica confortável para o dia a dia.</p><ul><li>100% algodão</li><li>Disponível em várias cores</li><li>Diversos tamanhos</li></ul>',
            'content' => '<p>Uma camiseta básica confortável para o dia a dia.</p><ul><li>100% algodão</li><li>Disponível em várias cores</li><li>Diversos tamanhos</li></ul>',
            'published_at' => date('Y-m-d\TH:i:s'),
            'created_at' => date('Y-m-d\TH:i:s', strtotime('-1 month')),
            'updated_at' => date('Y-m-d\TH:i:s', strtotime('-1 day')),
            'vendor' => 'Marca Demo',
            'product_type' => 'Camisetas',
            'tags' => ['básico', 'algodão', 'casual'],
            'price' => 4990,
            'price_min' => 4990,
            'price_max' => 5990,
            'price_varies' => true,
            'compare_at_price' => 6990,
            'compare_at_price_min' => 6990,
            'compare_at_price_max' => 7990,
            'compare_at_price_varies' => true,
            'available' => true,
            'selected' => false,
            'has_only_default_variant' => false,
            'options' => [
                [
                    'name' => 'Cor',
                    'position' => 1,
                    'values' => ['Branco', 'Preto', 'Azul', 'Vermelho']
                ],
                [
                    'name' => 'Tamanho',
                    'position' => 2,
                    'values' => ['P', 'M', 'G', 'GG']
                ]
            ],
            'options_with_values' => [
                [
                    'name' => 'Cor',
                    'position' => 1,
                    'values' => [
                        ['title' => 'Branco', 'value' => 'Branco'],
                        ['title' => 'Preto', 'value' => 'Preto'],
                        ['title' => 'Azul', 'value' => 'Azul'],
                        ['title' => 'Vermelho', 'value' => 'Vermelho']
                    ]
                ],
                [
                    'name' => 'Tamanho',
                    'position' => 2,
                    'values' => [
                        ['title' => 'P', 'value' => 'P'],
                        ['title' => 'M', 'value' => 'M'],
                        ['title' => 'G', 'value' => 'G'],
                        ['title' => 'GG', 'value' => 'GG']
                    ]
                ]
            ],
            'images' => [
                [
                    'id' => 1,
                    'src' => 'https://cdn.shopify.com/s/files/1/0000/0000/0000/products/camiseta-branca.jpg',
                    'alt' => 'Camiseta Branca',
                    'width' => 800,
                    'height' => 800,
                    'position' => 1,
                    'variant_ids' => [1, 2, 3, 4]
                ],
                [
                    'id' => 2,
                    'src' => 'https://cdn.shopify.com/s/files/1/0000/0000/0000/products/camiseta-preta.jpg',
                    'alt' => 'Camiseta Preta',
                    'width' => 800,
                    'height' => 800,
                    'position' => 2,
                    'variant_ids' => [5, 6, 7, 8]
                ]
            ],
            'featured_image' => [
                'id' => 1,
                'src' => 'https://cdn.shopify.com/s/files/1/0000/0000/0000/products/camiseta-branca.jpg',
                'alt' => 'Camiseta Branca',
                'width' => 800,
                'height' => 800,
                'position' => 1,
                'variant_ids' => [1, 2, 3, 4]
            ],
            'media' => [
                [
                    'id' => 1,
                    'position' => 1,
                    'alt' => 'Camiseta Branca',
                    'aspect_ratio' => 1.0,
                    'height' => 800,
                    'width' => 800,
                    'media_type' => 'image',
                    'preview_image' => [
                        'src' => 'https://cdn.shopify.com/s/files/1/0000/0000/0000/products/camiseta-branca.jpg',
                        'alt' => 'Camiseta Branca',
                        'width' => 800,
                        'height' => 800
                    ],
                    'variants' => [1, 2, 3, 4]
                ],
                [
                    'id' => 2,
                    'position' => 2,
                    'alt' => 'Camiseta Preta',
                    'aspect_ratio' => 1.0,
                    'height' => 800,
                    'width' => 800,
                    'media_type' => 'image',
                    'preview_image' => [
                        'src' => 'https://cdn.shopify.com/s/files/1/0000/0000/0000/products/camiseta-preta.jpg',
                        'alt' => 'Camiseta Preta',
                        'width' => 800,
                        'height' => 800
                    ],
                    'variants' => [5, 6, 7, 8]
                ]
            ],
            'variants' => [
                [
                    'id' => 1,
                    'title' => 'Branco / P',
                    'option1' => 'Branco',
                    'option2' => 'P',
                    'option3' => null,
                    'sku' => 'CB-BR-P-001',
                    'requires_shipping' => true,
                    'taxable' => true,
                    'featured_image' => [
                        'id' => 1,
                        'src' => 'https://cdn.shopify.com/s/files/1/0000/0000/0000/products/camiseta-branca.jpg',
                        'alt' => 'Camiseta Branca',
                        'width' => 800,
                        'height' => 800,
                        'position' => 1,
                        'variant_ids' => [1, 2, 3, 4]
                    ],
                    'available' => true,
                    'price' => 4990,
                    'compare_at_price' => 6990,
                    'position' => 1,
                    'weight' => 200,
                    'weight_unit' => 'g',
                    'inventory_policy' => 'deny',
                    'inventory_quantity' => 10,
                    'inventory_management' => 'shopify',
                    'barcode' => '1234567890123',
                    'requires_selling_plan' => false,
                    'selling_plan_allocations' => [],
                    'quantity_rule' => [
                        'min' => 1,
                        'max' => null,
                        'increment' => 1
                    ],
                    'quantity_price_breaks' => [],
                    'unit_price_measurement' => null
                ],
                // Outros variants simplificados para brevidade
                [
                    'id' => 2,
                    'title' => 'Branco / M',
                    'option1' => 'Branco',
                    'option2' => 'M',
                    'available' => true,
                    'price' => 4990,
                    'compare_at_price' => 6990
                ],
                [
                    'id' => 5,
                    'title' => 'Preto / P',
                    'option1' => 'Preto',
                    'option2' => 'P',
                    'available' => true,
                    'price' => 4990,
                    'compare_at_price' => 6990
                ]
            ],
            'collections' => [
                [
                    'id' => 1,
                    'handle' => 'camisetas',
                    'title' => 'Camisetas',
                    'url' => '/collections/camisetas'
                ],
                [
                    'id' => 2,
                    'handle' => 'basicos',
                    'title' => 'Básicos',
                    'url' => '/collections/basicos'
                ]
            ],
            'template_suffix' => '',
            'metafields' => [
                'reviews' => [
                    'rating' => [
                        'value' => 4.5,
                        'scale_min' => 1,
                        'scale_max' => 5
                    ],
                    'rating_count' => 24
                ]
            ],
            'url' => '/products/camiseta-basica',
            'featured_media' => [
                'id' => 1,
                'position' => 1,
                'alt' => 'Camiseta Branca',
                'aspect_ratio' => 1.0,
                'height' => 800,
                'width' => 800,
                'media_type' => 'image',
                'preview_image' => [
                    'src' => 'https://cdn.shopify.com/s/files/1/0000/0000/0000/products/camiseta-branca.jpg',
                    'alt' => 'Camiseta Branca',
                    'width' => 800,
                    'height' => 800
                ]
            ],
            'selected_variant' => null,
            'tags_array' => ['básico', 'algodão', 'casual'],
            'has_only_default_variant' => false,
            'requires_selling_plan' => false,
            'selling_plan_groups' => [],
            'selling_plans' => []
        ];
    }
    
    private function getRelatedProducts(): array
    {
        return [
            [
                'id' => 2345678901,
                'title' => 'Calça Jeans',
                'handle' => 'calca-jeans',
                'description' => 'Calça jeans básica com ótimo caimento.',
                'price' => 14990,
                'compare_at_price' => 19990,
                'available' => true,
                'featured_image' => [
                    'src' => 'https://cdn.shopify.com/s/files/1/0000/0000/0000/products/calca-jeans.jpg',
                    'alt' => 'Calça Jeans'
                ],
                'url' => '/products/calca-jeans'
            ],
            [
                'id' => 3456789012,
                'title' => 'Jaqueta Jeans',
                'handle' => 'jaqueta-jeans',
                'description' => 'Jaqueta jeans estilosa para ocasiões casuais.',
                'price' => 19990,
                'compare_at_price' => 24990,
                'available' => true,
                'featured_image' => [
                    'src' => 'https://cdn.shopify.com/s/files/1/0000/0000/0000/products/jaqueta-jeans.jpg',
                    'alt' => 'Jaqueta Jeans'
                ],
                'url' => '/products/jaqueta-jeans'
            ]
        ];
    }
    
    private function getProductCollection(): array
    {
        return [
            'id' => 1,
            'handle' => 'camisetas',
            'title' => 'Camisetas',
            'description' => 'Coleção de camisetas básicas para o dia a dia',
            'published_at' => date('Y-m-d\TH:i:s', strtotime('-3 months')),
            'updated_at' => date('Y-m-d\TH:i:s', strtotime('-1 week')),
            'url' => '/collections/camisetas',
            'products_count' => 12,
            'all_products_count' => 12,
            'all_types_count' => 1,
            'all_vendors_count' => 4,
            'featured_image' => [
                'src' => 'https://cdn.shopify.com/s/files/1/0000/0000/0000/collections/camisetas.jpg',
                'alt' => 'Coleção de Camisetas'
            ],
            'previous_product' => null,
            'next_product' => [
                'id' => 2345678901,
                'title' => 'Camiseta Gola V',
                'handle' => 'camiseta-gola-v',
                'url' => '/products/camiseta-gola-v'
            ]
        ];
    }
}