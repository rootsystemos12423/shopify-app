<?php

namespace App\Services;

use Liquid\Context;

class SectionProvider implements ContextProvider
{
    public function provide(Context $context, array $params): void
    {
        // Adicionar seções para a página atual
        $context->set('section', $this->getCurrentSectionData());
        $context->set('sections', $this->getAllSectionsData());
    }
    
    private function getCurrentSectionData(): array
    {
        return [
            'id' => 'header',
            'settings' => [
                'logo' => [
                    'src' => '/assets/logo.png',
                    'width' => 200,
                    'height' => 60,
                    'alt' => 'Logo da Loja Demo'
                ],
                'menu' => 'main-menu',
                'sticky_header' => true,
                'show_search' => true,
                'show_cart' => true,
                'background_color' => '#ffffff',
                'text_color' => '#000000',
                'padding_top' => 20,
                'padding_bottom' => 20
            ],
            'blocks' => [
                [
                    'id' => 'announcement',
                    'type' => 'announcement',
                    'settings' => [
                        'text' => 'Frete grátis para compras acima de R$ 99,90',
                        'link' => '/pages/entrega',
                        'color' => '#000000',
                        'background' => '#f5f5f5'
                    ]
                ],
                [
                    'id' => 'promo',
                    'type' => 'promo',
                    'settings' => [
                        'text' => 'Cupom BEMVINDO10: 10% de desconto na primeira compra',
                        'link' => '/collections/all',
                        'color' => '#ffffff',
                        'background' => '#ff0000'
                    ]
                ]
            ],
            'block_order' => ['announcement', 'promo']
        ];
    }
    
    private function getAllSectionsData(): array
    {
        return [
            'header' => [
                'id' => 'header',
                'type' => 'header',
                'settings' => [
                    'logo' => [
                        'src' => '/assets/logo.png',
                        'width' => 200,
                        'height' => 60,
                        'alt' => 'Logo da Loja Demo'
                    ],
                    'menu' => 'main-menu',
                    'sticky_header' => true,
                    'show_search' => true,
                    'show_cart' => true,
                    'background_color' => '#ffffff',
                    'text_color' => '#000000',
                    'padding_top' => 20,
                    'padding_bottom' => 20
                ],
                'blocks' => [
                    'announcement' => [
                        'type' => 'announcement',
                        'settings' => [
                            'text' => 'Frete grátis para compras acima de R$ 99,90',
                            'link' => '/pages/entrega',
                            'color' => '#000000',
                            'background' => '#f5f5f5'
                        ]
                    ],
                    'promo' => [
                        'type' => 'promo',
                        'settings' => [
                            'text' => 'Cupom BEMVINDO10: 10% de desconto na primeira compra',
                            'link' => '/collections/all',
                            'color' => '#ffffff',
                            'background' => '#ff0000'
                        ]
                    ]
                ],
                'block_order' => ['announcement', 'promo']
            ],
            'footer' => [
                'id' => 'footer',
                'type' => 'footer',
                'settings' => [
                    'logo' => [
                        'src' => '/assets/logo.png',
                        'width' => 150,
                        'height' => 45,
                        'alt' => 'Logo da Loja Demo'
                    ],
                    'show_payment_icons' => true,
                    'show_social_icons' => true,
                    'menu' => 'footer',
                    'copyright_text' => '© 2025 Loja Demo. Todos os direitos reservados.',
                    'background_color' => '#f5f5f5',
                    'text_color' => '#333333',
                    'padding_top' => 40,
                    'padding_bottom' => 40
                ],
                'blocks' => [
                    'newsletter' => [
                        'type' => 'newsletter',
                        'settings' => [
                            'title' => 'Inscreva-se em nossa newsletter',
                            'text' => 'Receba ofertas exclusivas e novidades diretamente em seu e-mail.',
                            'button_text' => 'Inscrever-se',
                            'show_social' => true
                        ]
                    ],
                    'contact' => [
                        'type' => 'contact',
                        'settings' => [
                            'title' => 'Entre em contato',
                            'phone' => '(11) 9999-9999',
                            'email' => 'contato@lojademo.com.br',
                            'address' => 'Rua Exemplo, 123 - São Paulo/SP'
                        ]
                    ]
                ],
                'block_order' => ['newsletter', 'contact']
            ],
            'featured-collection' => [
                'id' => 'featured-collection',
                'type' => 'featured-collection',
                'settings' => [
                    'title' => 'Produtos em Destaque',
                    'collection' => 'lancamentos',
                    'products_to_show' => 4,
                    'columns_desktop' => 4,
                    'full_width' => false,
                    'show_view_all' => true,
                    'view_all_style' => 'solid',
                    'enable_desktop_slider' => false,
                    'show_secondary_image' => true,
                    'show_vendor' => false,
                    'show_rating' => true
                ]
            ],
            'image-with-text' => [
                'id' => 'image-with-text',
                'type' => 'image-with-text',
                'settings' => [
                    'image' => [
                        'src' => '/assets/banner.jpg',
                        'width' => 1200,
                        'height' => 600,
                        'alt' => 'Banner Promocional'
                    ],
                    'height' => 'adapt',
                    'layout' => 'image_first',
                    'title' => 'Nova Coleção Verão 2025',
                    'text' => '<p>Peças leves, confortáveis e estilosas para os dias mais quentes do ano. Confira nossa nova coleção e renove seu guarda-roupa.</p>',
                    'button_label' => 'Comprar Agora',
                    'button_link' => '/collections/verao'
                ]
            ]
        ];
    }
}