<?php

namespace App\Services;

use Liquid\Context;

class CollectionProvider implements ContextProvider
{
    public function provide(Context $context, array $params): void
    {
        // Global collections
        $context->set('collections', $this->getAllCollections());
        
        // Current collection for collection pages
        if ($params['template'] === 'collection') {
            $context->set('collection', $this->getCurrentCollection());
            $context->set('collection_tags', $this->getCollectionTags());
            $context->set('collection_filters', $this->getCollectionFilters());
            $context->set('collection_sorting', $this->getCollectionSorting());
            $context->set('featured_collection', $this->getFeaturedCollection());
        }
    }
    
    private function getAllCollections(): array
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
                'featured_image' => [
                    'src' => 'https://cdn.shopify.com/s/files/1/0000/0000/0000/collections/all.jpg',
                    'alt' => 'Todos os Produtos'
                ]
            ],
            'camisetas' => [
                'id' => 2,
                'handle' => 'camisetas',
                'title' => 'Camisetas',
                'description' => 'Camisetas para todos os estilos',
                'url' => '/collections/camisetas',
                'products_count' => 8,
                'all_products_count' => 8,
                'featured_image' => [
                    'src' => 'https://cdn.shopify.com/s/files/1/0000/0000/0000/collections/camisetas.jpg',
                    'alt' => 'Camisetas'
                ]
            ],
            'calcas' => [
                'id' => 3,
                'handle' => 'calcas',
                'title' => 'Calças',
                'description' => 'Calças para todos os estilos',
                'url' => '/collections/calcas',
                'products_count' => 5,
                'all_products_count' => 5,
                'featured_image' => [
                    'src' => 'https://cdn.shopify.com/s/files/1/0000/0000/0000/collections/calcas.jpg',
                    'alt' => 'Calças'
                ]
            ],
            'acessorios' => [
                'id' => 4,
                'handle' => 'acessorios',
                'title' => 'Acessórios',
                'description' => 'Acessórios para complementar seu estilo',
                'url' => '/collections/acessorios',
                'products_count' => 7,
                'all_products_count' => 7,
                'featured_image' => [
                    'src' => 'https://cdn.shopify.com/s/files/1/0000/0000/0000/collections/acessorios.jpg',
                    'alt' => 'Acessórios'
                ]
            ]
        ];
    }
    
    private function getCurrentCollection(): array
    {
        return [
            'id' => 2,
            'handle' => 'camisetas',
            'title' => 'Camisetas',
            'description' => '<p>Camisetas para todos os estilos, feitas com materiais de alta qualidade para garantir conforto e durabilidade.</p>',
            'published_at' => date('Y-m-d\TH:i:s', strtotime('-3 months')),
            'updated_at' => date('Y-m-d\TH:i:s', strtotime('-1 week')),
            'products_count' => 8,
            'all_products_count' => 8,
            'all_types_count' => 1,
            'all_vendors_count' => 3,
            'all_tags' => ['casual', 'algodão', 'básico', 'estampada', 'esportiva', 'slim', 'gola-v', 'manga-longa'],
            'url' => '/collections/camisetas',
            'products' => $this->getCollectionProducts(),
            'featured_image' => [
                'src' => 'https://cdn.shopify.com/s/files/1/0000/0000/0000/collections/camisetas.jpg',
                'alt' => 'Coleção de Camisetas',
                'width' => 1200,
                'height' => 800,
                'aspect_ratio' => 1.5
            ],
            'metafields' => [],
            'default_sort_by' => 'manual',
            'sort_by' => 'best-selling',
            'template_suffix' => '',
            'sort_options' => [
                ['value' => 'manual', 'name' => 'Em destaque'],
                ['value' => 'best-selling', 'name' => 'Mais vendidos'],
                ['value' => 'title-ascending', 'name' => 'Ordem alfabética, A-Z'],
                ['value' => 'title-descending', 'name' => 'Ordem alfabética, Z-A'],
                ['value' => 'price-ascending', 'name' => 'Preço: menor para maior'],
                ['value' => 'price-descending', 'name' => 'Preço: maior para menor'],
                ['value' => 'created-ascending', 'name' => 'Data: antigos para recentes'],
                ['value' => 'created-descending', 'name' => 'Data: recentes para antigos']
            ],
            'previous_product' => null,
            'next_product' => null
        ];
    }
    
    private function getCollectionProducts(): array
    {
        return [
            [
                'id' => 1234567890,
                'title' => 'Camiseta Básica',
                'handle' => 'camiseta-basica',
                'description' => 'Camiseta básica de algodão, ideal para o dia a dia.',
                'published_at' => date('Y-m-d\TH:i:s', strtotime('-2 months')),
                'created_at' => date('Y-m-d\TH:i:s', strtotime('-2 months')),
                'vendor' => 'Marca Demo',
                'type' => 'Camiseta',
                'tags' => ['casual', 'básico', 'algodão'],
                'price' => 4990,
                'price_min' => 4990,
                'price_max' => 4990,
                'available' => true,
                'price_varies' => false,
                'compare_at_price' => 5990,
                'compare_at_price_min' => 5990,
                'compare_at_price_max' => 5990,
                'compare_at_price_varies' => false,
                'variants' => [
                    [
                        'id' => 12345,
                        'title' => 'Branco / P',
                        'option1' => 'Branco',
                        'option2' => 'P',
                        'available' => true,
                        'price' => 4990
                    ]
                ],
                'featured_image' => [
                    'src' => 'https://cdn.shopify.com/s/files/1/0000/0000/0000/products/camiseta-basica.jpg',
                    'alt' => 'Camiseta Básica'
                ],
                'images' => [
                    [
                        'src' => 'https://cdn.shopify.com/s/files/1/0000/0000/0000/products/camiseta-basica.jpg',
                        'alt' => 'Camiseta Básica'
                    ]
                ],
                'options' => [
                    [
                        'name' => 'Cor',
                        'position' => 1,
                        'values' => ['Branco', 'Preto', 'Azul']
                    ],
                    [
                        'name' => 'Tamanho',
                        'position' => 2,
                        'values' => ['P', 'M', 'G', 'GG']
                    ]
                ],
                'url' => '/products/camiseta-basica'
            ],
            [
                'id' => 1234567891,
                'title' => 'Camiseta Estampada',
                'handle' => 'camiseta-estampada',
                'description' => 'Camiseta com estampa moderna.',
                'published_at' => date('Y-m-d\TH:i:s', strtotime('-1 month')),
                'created_at' => date('Y-m-d\TH:i:s', strtotime('-1 month')),
                'vendor' => 'Marca Demo',
                'type' => 'Camiseta',
                'tags' => ['casual', 'estampada'],
                'price' => 5990,
                'price_min' => 5990,
                'price_max' => 5990,
                'available' => true,
                'price_varies' => false,
                'compare_at_price' => 7990,
                'compare_at_price_min' => 7990,
                'compare_at_price_max' => 7990,
                'compare_at_price_varies' => false,
                'variants' => [
                    [
                        'id' => 12346,
                        'title' => 'Azul / M',
                        'option1' => 'Azul',
                        'option2' => 'M',
                        'available' => true,
                        'price' => 5990
                    ]
                ],
                'featured_image' => [
                    'src' => 'https://cdn.shopify.com/s/files/1/0000/0000/0000/products/camiseta-estampada.jpg',
                    'alt' => 'Camiseta Estampada'
                ],
                'images' => [
                    [
                        'src' => 'https://cdn.shopify.com/s/files/1/0000/0000/0000/products/camiseta-estampada.jpg',
                        'alt' => 'Camiseta Estampada'
                    ]
                ],
                'options' => [
                    [
                        'name' => 'Cor',
                        'position' => 1,
                        'values' => ['Azul', 'Vermelho']
                    ],
                    [
                        'name' => 'Tamanho',
                        'position' => 2,
                        'values' => ['P', 'M', 'G']
                    ]
                ],
                'url' => '/products/camiseta-estampada'
            ]
        ];
    }
    
    private function getCollectionTags(): array
    {
        return [
            'casual' => [
                'active' => false,
                'count' => 5,
                'url' => '/collections/camisetas/casual'
            ],
            'algodão' => [
                'active' => false,
                'count' => 6,
                'url' => '/collections/camisetas/algodao'
            ],
            'básico' => [
                'active' => false,
                'count' => 4,
                'url' => '/collections/camisetas/basico'
            ],
            'estampada' => [
                'active' => false,
                'count' => 3,
                'url' => '/collections/camisetas/estampada'
            ],
            'esportiva' => [
                'active' => false,
                'count' => 2,
                'url' => '/collections/camisetas/esportiva'
            ]
        ];
    }
    
    private function getCollectionFilters(): array
    {
        return [
            [
                'id' => 'filter-1',
                'label' => 'Disponibilidade',
                'type' => 'boolean',
                'values' => [
                    [
                        'id' => 'disponivel',
                        'label' => 'Em estoque',
                        'count' => 8,
                        'active' => false,
                        'url' => '/collections/camisetas?filter.v.availability=1'
                    ],
                    [
                        'id' => 'indisponivel',
                        'label' => 'Esgotado',
                        'count' => 0,
                        'active' => false,
                        'url' => '/collections/camisetas?filter.v.availability=0'
                    ]
                ]
            ],
            [
                'id' => 'filter-2',
                'label' => 'Preço',
                'type' => 'price_range',
                'values' => [
                    [
                        'id' => 'preco-1',
                        'label' => 'Abaixo de R$ 50,00',
                        'count' => 3,
                        'active' => false,
                        'url' => '/collections/camisetas?filter.v.price=:50'
                    ],
                    [
                        'id' => 'preco-2',
                        'label' => 'R$ 50,00 - R$ 100,00',
                        'count' => 5,
                        'active' => false,
                        'url' => '/collections/camisetas?filter.v.price=50:100'
                    ]
                ]
            ],
            [
                'id' => 'filter-3',
                'label' => 'Cor',
                'type' => 'list',
                'values' => [
                    [
                        'id' => 'branco',
                        'label' => 'Branco',
                        'count' => 3,
                        'active' => false,
                        'url' => '/collections/camisetas?filter.v.option.color=Branco'
                    ],
                    [
                        'id' => 'preto',
                        'label' => 'Preto',
                        'count' => 3,
                        'active' => false,
                        'url' => '/collections/camisetas?filter.v.option.color=Preto'
                    ],
                    [
                        'id' => 'azul',
                        'label' => 'Azul',
                        'count' => 2,
                        'active' => false,
                        'url' => '/collections/camisetas?filter.v.option.color=Azul'
                    ]
                ]
            ],
            [
                'id' => 'filter-4',
                'label' => 'Tamanho',
                'type' => 'list',
                'values' => [
                    [
                        'id' => 'p',
                        'label' => 'P',
                        'count' => 8,
                        'active' => false,
                        'url' => '/collections/camisetas?filter.v.option.size=P'
                    ],
                    [
                        'id' => 'm',
                        'label' => 'M',
                        'count' => 8,
                        'active' => false,
                        'url' => '/collections/camisetas?filter.v.option.size=M'
                    ],
                    [
                        'id' => 'g',
                        'label' => 'G',
                        'count' => 8,
                        'active' => false,
                        'url' => '/collections/camisetas?filter.v.option.size=G'
                    ],
                    [
                        'id' => 'gg',
                        'label' => 'GG',
                        'count' => 6,
                        'active' => false,
                        'url' => '/collections/camisetas?filter.v.option.size=GG'
                    ]
                ]
            ]
        ];
    }
    
    private function getCollectionSorting(): array
    {
        return [
            'applied' => 'best-selling',
            'options' => [
                [
                    'value' => 'manual',
                    'name' => 'Em destaque',
                    'active' => false,
                    'url' => '/collections/camisetas?sort_by=manual'
                ],
                [
                    'value' => 'best-selling',
                    'name' => 'Mais vendidos',
                    'active' => true,
                    'url' => '/collections/camisetas?sort_by=best-selling'
                ],
                [
                    'value' => 'title-ascending',
                    'name' => 'Ordem alfabética, A-Z',
                    'active' => false,
                    'url' => '/collections/camisetas?sort_by=title-ascending'
                ],
                [
                    'value' => 'title-descending',
                    'name' => 'Ordem alfabética, Z-A',
                    'active' => false,
                    'url' => '/collections/camisetas?sort_by=title-descending'
                ],
                [
                    'value' => 'price-ascending',
                    'name' => 'Preço: menor para maior',
                    'active' => false,
                    'url' => '/collections/camisetas?sort_by=price-ascending'
                ],
                [
                    'value' => 'price-descending',
                    'name' => 'Preço: maior para menor',
                    'active' => false,
                    'url' => '/collections/camisetas?sort_by=price-descending'
                ],
                [
                    'value' => 'created-ascending',
                    'name' => 'Data: antigos para recentes',
                    'active' => false,
                    'url' => '/collections/camisetas?sort_by=created-ascending'
                ],
                [
                    'value' => 'created-descending',
                    'name' => 'Data: recentes para antigos',
                    'active' => false,
                    'url' => '/collections/camisetas?sort_by=created-descending'
                ]
            ]
        ];
    }
    
    private function getFeaturedCollection(): array
    {
        return [
            'id' => 5,
            'handle' => 'lancamentos',
            'title' => 'Lançamentos',
            'description' => 'Os produtos mais recentes da loja',
            'published_at' => date('Y-m-d\TH:i:s', strtotime('-1 week')),
            'updated_at' => date('Y-m-d\TH:i:s', strtotime('-1 day')),
            'products_count' => 4,
            'all_products_count' => 4,
            'url' => '/collections/lancamentos',
            'featured_image' => [
                'src' => 'https://cdn.shopify.com/s/files/1/0000/0000/0000/collections/lancamentos.jpg',
                'alt' => 'Lançamentos',
                'width' => 1200,
                'height' => 800,
                'aspect_ratio' => 1.5
            ],
            'products' => []
        ];
    }
}