<?php

namespace App\Services;

use Liquid\Context;

class PaginationProvider implements ContextProvider
{
    public function provide(Context $context, array $params): void
    {
        // Adicionar paginação para páginas que suportam
        if (in_array($params['template'], ['collection', 'search', 'blog', 'customers/orders'])) {
            $context->set('paginate', $this->getPaginationData());
        }
    }
    
    private function getPaginationData(): array
    {
        return [
            'current_page' => 1,
            'current_offset' => 0,
            'items' => 20,
            'page_size' => 20,
            'parts' => [
                [
                    'is_link' => false,
                    'title' => '1',
                    'url' => null
                ],
                [
                    'is_link' => true,
                    'title' => '2',
                    'url' => '/collections/all?page=2'
                ],
                [
                    'is_link' => true,
                    'title' => '3',
                    'url' => '/collections/all?page=3'
                ]
            ],
            'next' => [
                'title' => '2',
                'url' => '/collections/all?page=2',
                'is_link' => true
            ],
            'previous' => null,
            'total_pages' => 3,
            'pages' => 3,
            'results_count' => 56,
            'items_offset' => 0,
            'items_total' => 56
        ];
    }
}