<?php

namespace App\Services;

use Liquid\Context;

class MediaProvider implements ContextProvider
{
    public function provide(Context $context, array $params): void
    {
        // Adicionar objeto de mídia para páginas de produto
        if ($params['template'] === 'product') {
            $context->set('media', $this->getMediaData());
            $context->set('featured_media', $this->getFeaturedMediaData());
        }
    }
    
    private function getMediaData(): array
    {
        return [
            [
                'id' => 1,
                'position' => 1,
                'preview_image' => [
                    'src' => 'https://cdn.shopify.com/s/files/1/0000/0000/0000/products/camiseta-basica-frente.jpg',
                    'alt' => 'Camiseta Básica - Frente',
                    'width' => 800,
                    'height' => 800,
                    'aspect_ratio' => 1.0
                ],
                'aspect_ratio' => 1.0,
                'height' => 800,
                'width' => 800,
                'media_type' => 'image',
                'url' => 'https://cdn.shopify.com/s/files/1/0000/0000/0000/products/camiseta-basica-frente.jpg',
                'alt' => 'Camiseta Básica - Frente'
            ],
            [
                'id' => 2,
                'position' => 2,
                'preview_image' => [
                    'src' => 'https://cdn.shopify.com/s/files/1/0000/0000/0000/products/camiseta-basica-costas.jpg',
                    'alt' => 'Camiseta Básica - Costas',
                    'width' => 800,
                    'height' => 800,
                    'aspect_ratio' => 1.0
                ],
                'aspect_ratio' => 1.0,
                'height' => 800,
                'width' => 800,
                'media_type' => 'image',
                'url' => 'https://cdn.shopify.com/s/files/1/0000/0000/0000/products/camiseta-basica-costas.jpg',
                'alt' => 'Camiseta Básica - Costas'
            ],
            [
                'id' => 3,
                'position' => 3,
                'preview_image' => [
                    'src' => 'https://cdn.shopify.com/s/files/1/0000/0000/0000/products/camiseta-basica-detalhe.jpg',
                    'alt' => 'Camiseta Básica - Detalhe',
                    'width' => 800,
                    'height' => 800,
                    'aspect_ratio' => 1.0
                ],
                'aspect_ratio' => 1.0,
                'height' => 800,
                'width' => 800,
                'media_type' => 'image',
                'url' => 'https://cdn.shopify.com/s/files/1/0000/0000/0000/products/camiseta-basica-detalhe.jpg',
                'alt' => 'Camiseta Básica - Detalhe'
            ],
            [
                'id' => 4,
                'position' => 4,
                'preview_image' => [
                    'src' => 'https://cdn.shopify.com/s/files/1/0000/0000/0000/products/camiseta-basica-video-thumb.jpg',
                    'alt' => 'Camiseta Básica - Vídeo',
                    'width' => 800,
                    'height' => 800,
                    'aspect_ratio' => 1.0
                ],
                'aspect_ratio' => 1.0,
                'height' => 800,
                'width' => 800,
                'media_type' => 'video',
                'url' => 'https://cdn.shopify.com/s/files/1/0000/0000/0000/products/camiseta-basica-video.mp4',
                'sources' => [
                    [
                        'format' => 'mp4',
                        'url' => 'https://cdn.shopify.com/s/files/1/0000/0000/0000/products/camiseta-basica-video.mp4',
                        'mime_type' => 'video/mp4',
                        'width' => 800,
                        'height' => 800
                    ],
                    [
                        'format' => 'webm',
                        'url' => 'https://cdn.shopify.com/s/files/1/0000/0000/0000/products/camiseta-basica-video.webm',
                        'mime_type' => 'video/webm',
                        'width' => 800,
                        'height' => 800
                    ]
                ],
                'alt' => 'Camiseta Básica - Vídeo',
                'host' => 'shopify',
                'external_id' => null
            ]
        ];
    }
    
    private function getFeaturedMediaData(): array
    {
        return [
            'id' => 1,
            'position' => 1,
            'preview_image' => [
                'src' => 'https://cdn.shopify.com/s/files/1/0000/0000/0000/products/camiseta-basica-frente.jpg',
                'alt' => 'Camiseta Básica - Frente',
                'width' => 800,
                'height' => 800,
                'aspect_ratio' => 1.0
            ],
            'aspect_ratio' => 1.0,
            'height' => 800,
            'width' => 800,
            'media_type' => 'image',
            'url' => 'https://cdn.shopify.com/s/files/1/0000/0000/0000/products/camiseta-basica-frente.jpg',
            'alt' => 'Camiseta Básica - Frente'
        ];
    }
}