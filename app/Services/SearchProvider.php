<?php

namespace App\Services;

use Liquid\Context;

class SearchProvider implements ContextProvider
{
    public function provide(Context $context, array $params): void
    {
        // Adiciona dados de busca somente se estamos em uma página de busca
        if ($params['template'] === 'search') {
            $context->set('search', $this->getSearchData());
        }
    }
    
    private function getSearchData(): array
    {
        return [
            'terms' => 'camiseta',
            'performed' => true,
            'results' => $this->getSearchResults(),
            'results_count' => 3,
            'unavailable_products' => 0,
            'filters' => $this->getSearchFilters(),
            'types' => [
                'product' => 3,
                'article' => 1,
                'page' => 0
            ],
            'sort_by' => 'relevance',
            'sort_options' => [
                [
                    'name' => 'Relevância',
                    'value' => 'relevance',
                    'active' => true,
                    'url' => '/search?q=camiseta&sort_by=relevance'
                ],
                [
                    'name' => 'Mais vendidos',
                    'value' => 'best-selling',
                    'active' => false,
                    'url' => '/search?q=camiseta&sort_by=best-selling'
                ],
                [
                    'name' => 'Alfabética, A-Z',
                    'value' => 'title-ascending',
                    'active' => false,
                    'url' => '/search?q=camiseta&sort_by=title-ascending'
                ],
                [
                    'name' => 'Alfabética, Z-A',
                    'value' => 'title-descending',
                    'active' => false,
                    'url' => '/search?q=camiseta&sort_by=title-descending'
                ],
                [
                    'name' => 'Preço, menor para maior',
                    'value' => 'price-ascending',
                    'active' => false,
                    'url' => '/search?q=camiseta&sort_by=price-ascending'
                ],
                [
                    'name' => 'Preço, maior para menor',
                    'value' => 'price-descending',
                    'active' => false,
                    'url' => '/search?q=camiseta&sort_by=price-descending'
                ],
                [
                    'name' => 'Data, mais antigos',
                    'value' => 'created-ascending',
                    'active' => false,
                    'url' => '/search?q=camiseta&sort_by=created-ascending'
                ],
                [
                    'name' => 'Data, mais recentes',
                    'value' => 'created-descending',
                    'active' => false,
                    'url' => '/search?q=camiseta&sort_by=created-descending'
                ]
            ],
            'resources' => [
                'options' => [
                    [
                        'name' => 'Produtos',
                        'value' => 'product',
                        'active' => true,
                        'count' => 3,
                        'url' => '/search?q=camiseta&type=product'
                    ],
                    [
                        'name' => 'Artigos',
                        'value' => 'article',
                        'active' => false,
                        'count' => 1,
                        'url' => '/search?q=camiseta&type=article'
                    ],
                    [
                        'name' => 'Páginas',
                        'value' => 'page',
                        'active' => false,
                        'count' => 0,
                        'url' => '/search?q=camiseta&type=page'
                    ]
                ],
                'current' => 'product'
            ],
            'url' => '/search?q=camiseta',
            'canonical_url' => '/search?q=camiseta'
        ];
    }
    
    private function getSearchResults(): array
    {
        return [
            [
                'id' => 1234567890,
                'title' => 'Camiseta Básica',
                'handle' => 'camiseta-basica',
                'url' => '/products/camiseta-basica',
                'type' => 'product',
                'vendor' => 'Marca Demo',
                'tags' => ['casual', 'básico', 'algodão'],
                'featured_image' => [
                    'src' => 'https://cdn.shopify.com/s/files/1/0000/0000/0000/products/camiseta-basica.jpg',
                    'alt' => 'Camiseta Básica'
                ],
                'price' => 4990,
                'compare_at_price' => 5990,
                'available' => true,
                'options' => [
                    [
                        'name' => 'Cor',
                        'values' => ['Branco', 'Preto', 'Azul']
                    ],
                    [
                        'name' => 'Tamanho',
                        'values' => ['P', 'M', 'G', 'GG']
                    ]
                ],
                'variants' => [
                    [
                        'id' => 12345,
                        'title' => 'Branco / P',
                        'available' => true,
                        'price' => 4990
                    ]
                ],
                'created_at' => date('Y-m-d\TH:i:s', strtotime('-2 months')),
                'description' => 'Camiseta básica de algodão, ideal para o dia a dia'
            ],
            [
                'id' => 1234567891,
                'title' => 'Camiseta Estampada',
                'handle' => 'camiseta-estampada',
                'url' => '/products/camiseta-estampada',
                'type' => 'product',
                'vendor' => 'Marca Demo',
                'tags' => ['casual', 'estampada'],
                'featured_image' => [
                    'src' => 'https://cdn.shopify.com/s/files/1/0000/0000/0000/products/camiseta-estampada.jpg',
                    'alt' => 'Camiseta Estampada'
                ],
                'price' => 5990,
                'compare_at_price' => 7990,
                'available' => true,
                'options' => [
                    [
                        'name' => 'Cor',
                        'values' => ['Azul', 'Vermelho']
                    ],
                    [
                        'name' => 'Tamanho',
                        'values' => ['P', 'M', 'G']
                    ]
                ],
                'variants' => [
                    [
                        'id' => 12346,
                        'title' => 'Azul / M',
                        'available' => true,
                        'price' => 5990
                    ]
                ],
                'created_at' => date('Y-m-d\TH:i:s', strtotime('-1 month')),
                'description' => 'Camiseta com estampa moderna'
            ],
            [
                'id' => 1234567892,
                'title' => 'Camiseta Polo',
                'handle' => 'camiseta-polo',
                'url' => '/products/camiseta-polo',
                'type' => 'product',
                'vendor' => 'Marca Premium',
                'tags' => ['polo', 'social', 'algodão'],
                'featured_image' => [
                    'src' => 'https://cdn.shopify.com/s/files/1/0000/0000/0000/products/camiseta-polo.jpg',
                    'alt' => 'Camiseta Polo'
                ],
                'price' => 7990,
                'compare_at_price' => 9990,
                'available' => true,
                'options' => [
                    [
                        'name' => 'Cor',
                        'values' => ['Branco', 'Preto', 'Azul Marinho']
                    ],
                    [
                        'name' => 'Tamanho',
                        'values' => ['P', 'M', 'G', 'GG']
                    ]
                ],
                'variants' => [
                    [
                        'id' => 12347,
                        'title' => 'Preto / G',
                        'available' => true,
                        'price' => 7990
                    ]
                ],
                'created_at' => date('Y-m-d\TH:i:s', strtotime('-3 weeks')),
                'description' => 'Camiseta polo em algodão pima de alta qualidade'
            ],
            [
                'id' => 123456,
                'title' => 'Como Escolher a Camiseta Perfeita para o Seu Tipo de Corpo',
                'handle' => 'como-escolher-a-camiseta-perfeita',
                'url' => '/blogs/noticias/como-escolher-a-camiseta-perfeita',
                'type' => 'article',
                'author' => 'Maria Oliveira',
                'excerpt' => 'Saiba como escolher o modelo de camiseta ideal para valorizar o seu tipo de corpo.',
                'published_at' => date('Y-m-d\TH:i:s', strtotime('-2 weeks')),
                'blog' => [
                    'title' => 'Notícias e Novidades',
                    'handle' => 'noticias'
                ],
                'image' => [
                    'src' => 'https://cdn.shopify.com/s/files/1/0000/0000/0000/articles/escolha-camiseta.jpg',
                    'alt' => 'Como Escolher a Camiseta Perfeita'
                ]
            ]
        ];
    }
    
    private function getSearchFilters(): array
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
                        'count' => 3,
                        'active' => false,
                        'url' => '/search?q=camiseta&filter.v.availability=1'
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
                        'count' => 1,
                        'active' => false,
                        'url' => '/search?q=camiseta&filter.v.price=:50'
                    ],
                    [
                        'id' => 'preco-2',
                        'label' => 'R$ 50,00 - R$ 100,00',
                        'count' => 2,
                        'active' => false,
                        'url' => '/search?q=camiseta&filter.v.price=50:100'
                    ]
                ]
            ],
            [
                'id' => 'filter-3',
                'label' => 'Marca',
                'type' => 'list',
                'values' => [
                    [
                        'id' => 'marca-demo',
                        'label' => 'Marca Demo',
                        'count' => 2,
                        'active' => false,
                        'url' => '/search?q=camiseta&filter.p.vendor=Marca+Demo'
                    ],
                    [
                        'id' => 'marca-premium',
                        'label' => 'Marca Premium',
                        'count' => 1,
                        'active' => false,
                        'url' => '/search?q=camiseta&filter.p.vendor=Marca+Premium'
                    ]
                ]
            ]
        ];
    }
}