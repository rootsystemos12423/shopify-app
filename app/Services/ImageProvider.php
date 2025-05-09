<?php

namespace App\Services;

use Liquid\Context;

class ImageProvider implements ContextProvider
{
    public function provide(Context $context, array $params): void
    {
        // Adicionar objeto de imagem geral
        $context->set('image', $this->getImageData());
        
        // Adicionar filtros de imagem comuns
        $context->set('image_url', 'https://cdn.shopify.com/s/files/1/0000/0000/0000/products/default.jpg');
        $context->set('image_tag', '<img src="https://cdn.shopify.com/s/files/1/0000/0000/0000/products/default.jpg" alt="Imagem padrão">');
    }
    
    private function getImageData(): array
    {
        return [
            'id' => 1,
            'src' => 'https://cdn.shopify.com/s/files/1/0000/0000/0000/products/default.jpg',
            'alt' => 'Imagem padrão',
            'width' => 800,
            'height' => 800,
            'aspect_ratio' => 1.0,
            'position' => 1,
            'variants' => [
                '100x' => 'https://cdn.shopify.com/s/files/1/0000/0000/0000/products/default_100x.jpg',
                '200x' => 'https://cdn.shopify.com/s/files/1/0000/0000/0000/products/default_200x.jpg',
                '400x' => 'https://cdn.shopify.com/s/files/1/0000/0000/0000/products/default_400x.jpg',
                '800x' => 'https://cdn.shopify.com/s/files/1/0000/0000/0000/products/default_800x.jpg'
            ],
            'product_id' => null,
            'attached_to_variant_ids' => []
        ];
    }
}