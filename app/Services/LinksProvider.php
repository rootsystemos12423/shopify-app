<?php

namespace App\Services;

use Liquid\Context;

class LinksProvider implements ContextProvider
{
    public function provide(Context $context, array $params): void
    {
        // Adicionar menus da loja
        $context->set('linklists', $this->getLinklists());
    }
    
    private function getLinklists(): array
    {
        return [
            'main-menu' => [
                'handle' => 'main-menu',
                'title' => 'Menu Principal',
                'levels' => 2,
                'links' => [
                    [
                        'title' => 'Início',
                        'url' => '/',
                        'type' => 'frontpage_link',
                        'active' => true,
                        'links' => []
                    ],
                    [
                        'title' => 'Produtos',
                        'url' => '/collections/all',
                        'type' => 'collections_link',
                        'active' => false,
                        'links' => [
                            [
                                'title' => 'Camisetas',
                                'url' => '/collections/camisetas',
                                'type' => 'collection_link',
                                'active' => false
                            ],
                            [
                                'title' => 'Calças',
                                'url' => '/collections/calcas',
                                'type' => 'collection_link',
                                'active' => false
                            ],
                            [
                                'title' => 'Acessórios',
                                'url' => '/collections/acessorios',
                                'type' => 'collection_link',
                                'active' => false
                            ]
                        ]
                    ],
                    [
                        'title' => 'Ofertas',
                        'url' => '/collections/ofertas',
                        'type' => 'collection_link',
                        'active' => false,
                        'links' => []
                    ],
                    [
                        'title' => 'Sobre',
                        'url' => '/pages/sobre-nos',
                        'type' => 'page_link',
                        'active' => false,
                        'links' => []
                    ],
                    [
                        'title' => 'Contato',
                        'url' => '/pages/contato',
                        'type' => 'page_link',
                        'active' => false,
                        'links' => []
                    ]
                ]
            ],
            'footer' => [
                'handle' => 'footer',
                'title' => 'Rodapé',
                'levels' => 1,
                'links' => [
                    [
                        'title' => 'Sobre Nós',
                        'url' => '/pages/sobre-nos',
                        'type' => 'page_link',
                        'active' => false,
                        'links' => []
                    ],
                    [
                        'title' => 'Termos de Serviço',
                        'url' => '/policies/terms-of-service',
                        'type' => 'policy_link',
                        'active' => false,
                        'links' => []
                    ],
                    [
                        'title' => 'Política de Privacidade',
                        'url' => '/policies/privacy-policy',
                        'type' => 'policy_link',
                        'active' => false,
                        'links' => []
                    ],
                    [
                        'title' => 'Política de Reembolso',
                        'url' => '/policies/refund-policy',
                        'type' => 'policy_link',
                        'active' => false,
                        'links' => []
                    ],
                    [
                        'title' => 'FAQ',
                        'url' => '/pages/perguntas-frequentes',
                        'type' => 'page_link',
                        'active' => false,
                        'links' => []
                    ]
                ]
            ],
            'social' => [
                'handle' => 'social',
                'title' => 'Links Sociais',
                'levels' => 1,
                'links' => [
                    [
                        'title' => 'Facebook',
                        'url' => 'https://facebook.com/lojademo',
                        'type' => 'url_link',
                        'active' => false,
                        'links' => []
                    ],
                    [
                        'title' => 'Instagram',
                        'url' => 'https://instagram.com/lojademo',
                        'type' => 'url_link',
                        'active' => false,
                        'links' => []
                    ],
                    [
                        'title' => 'YouTube',
                        'url' => 'https://youtube.com/lojademo',
                        'type' => 'url_link',
                        'active' => false,
                        'links' => []
                    ],
                    [
                        'title' => 'Twitter',
                        'url' => 'https://twitter.com/lojademo',
                        'type' => 'url_link',
                        'active' => false,
                        'links' => []
                    ]
                ]
            ]
        ];
    }
}