<?php

namespace App\Services;

use Liquid\Context;

class FilterProvider implements ContextProvider
{
    public function provide(Context $context, array $params): void
    {
        // Adicionar filtros se estamos em uma página de coleção ou busca
        if (in_array($params['template'], ['collection', 'search'])) {
            $context->set('filters', $this->getFilters());
            $context->set('current_filters', $this->getCurrentFilters());
        }
    }
    
    private function getFilters(): array
    {
        return [
            [
                'id' => 'filter-availability',
                'label' => 'Disponibilidade',
                'type' => 'boolean',
                'param_name' => 'filter.v.availability',
                'values' => [
                    [
                        'id' => 'disponivel',
                        'label' => 'Em estoque',
                        'count' => 23,
                        'active' => false,
                        'url' => '/collections/all?filter.v.availability=1'
                    ]
                ]
            ],
            [
                'id' => 'filter-price',
                'label' => 'Preço',
                'type' => 'price_range',
                'param_name' => 'filter.v.price',
                'values' => [
                    [
                        'id' => 'preco-1',
                        'label' => 'Abaixo de R$ 50,00',
                        'count' => 8,
                        'active' => false,
                        'url' => '/collections/all?filter.v.price=:50'
                    ],
                    [
                        'id' => 'preco-2',
                        'label' => 'R$ 50,00 - R$ 100,00',
                        'count' => 12,
                        'active' => false,
                        'url' => '/collections/all?filter.v.price=50:100'
                    ],
                    [
                        'id' => 'preco-3',
                        'label' => 'Acima de R$ 100,00',
                        'count' => 5,
                        'active' => false,
                        'url' => '/collections/all?filter.v.price=100:'
                    ]
                ],
                'min' => 2990,
                'max' => 19990,
                'unit' => 'R$'
            ],
            [
                'id' => 'filter-cor',
                'label' => 'Cor',
                'type' => 'list',
                'param_name' => 'filter.v.option.color',
                'values' => [
                    [
                        'id' => 'branco',
                        'label' => 'Branco',
                        'count' => 10,
                        'active' => false,
                        'url' => '/collections/all?filter.v.option.color=Branco'
                    ],
                    [
                        'id' => 'preto',
                        'label' => 'Preto',
                        'count' => 12,
                        'active' => false,
                        'url' => '/collections/all?filter.v.option.color=Preto'
                    ],
                    [
                        'id' => 'azul',
                        'label' => 'Azul',
                        'count' => 8,
                        'active' => false,
                        'url' => '/collections/all?filter.v.option.color=Azul'
                    ],
                    [
                        'id' => 'vermelho',
                        'label' => 'Vermelho',
                        'count' => 5,
                        'active' => false,
                        'url' => '/collections/all?filter.v.option.color=Vermelho'
                    ]
                ]
            ],
            [
                'id' => 'filter-tamanho',
                'label' => 'Tamanho',
                'type' => 'list',
                'param_name' => 'filter.v.option.size',
                'values' => [
                    [
                        'id' => 'p',
                        'label' => 'P',
                        'count' => 15,
                        'active' => false,
                        'url' => '/collections/all?filter.v.option.size=P'
                    ],
                    [
                        'id' => 'm',
                        'label' => 'M',
                        'count' => 20,
                        'active' => false,
                        'url' => '/collections/all?filter.v.option.size=M'
                    ],
                    [
                        'id' => 'g',
                        'label' => 'G',
                        'count' => 18,
                        'active' => false,
                        'url' => '/collections/all?filter.v.option.size=G'
                    ],
                    [
                        'id' => 'gg',
                        'label' => 'GG',
                        'count' => 12,
                        'active' => false,
                        'url' => '/collections/all?filter.v.option.size=GG'
                    ]
                ]
            ],
            [
                'id' => 'filter-marca',
                'label' => 'Marca',
                'type' => 'list',
                'param_name' => 'filter.p.vendor',
                'values' => [
                    [
                        'id' => 'marca-demo',
                        'label' => 'Marca Demo',
                        'count' => 15,
                        'active' => false,
                        'url' => '/collections/all?filter.p.vendor=Marca+Demo'
                    ],
                    [
                        'id' => 'marca-premium',
                        'label' => 'Marca Premium',
                        'count' => 10,
                        'active' => false,
                        'url' => '/collections/all?filter.p.vendor=Marca+Premium'
                    ]
                ]
            ]
        ];
    }
    
    private function getCurrentFilters(): array
    {
        // Simulação de filtros ativos
        return [
            [
                'label' => 'Disponibilidade',
                'type' => 'boolean',
                'active_values' => [
                    [
                        'label' => 'Em estoque',
                        'remove_url' => '/collections/all'
                    ]
                ]
            ]
        ];
    }
}