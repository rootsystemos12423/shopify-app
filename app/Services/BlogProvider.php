<?php

namespace App\Services;

use Liquid\Context;

class BlogProvider implements ContextProvider
{
    public function provide(Context $context, array $params): void
    {
        // Adiciona dados de blog somente se estamos em uma página de blog ou artigo
        if (in_array($params['template'], ['blog', 'article'])) {
            $context->set('blog', $this->getBlogData());
            $context->set('blogs', $this->getAllBlogs());
            
            // Adiciona artigo específico para páginas de artigo
            if ($params['template'] === 'article') {
                $context->set('article', $this->getArticleData());
            } else {
                // Em páginas de blog, adicionamos a lista de artigos
                $context->set('articles', $this->getArticlesData());
            }
        }
    }
    
    private function getBlogData(): array
    {
        return [
            'id' => 111111,
            'handle' => 'noticias',
            'title' => 'Notícias e Novidades',
            'url' => '/blogs/noticias',
            'commentable' => 'moderate',
            'comments_enabled' => true,
            'moderated' => true,
            'articles_count' => 10,
            'tags' => ['moda', 'tendências', 'estilo', 'sustentabilidade'],
            'all_tags' => ['moda', 'tendências', 'estilo', 'sustentabilidade', 'dicas', 'eventos'],
            'metafields' => []
        ];
    }
    
    private function getAllBlogs(): array
    {
        return [
            'noticias' => [
                'id' => 111111,
                'handle' => 'noticias',
                'title' => 'Notícias e Novidades',
                'url' => '/blogs/noticias',
                'commentable' => 'moderate',
                'comments_enabled' => true,
                'moderated' => true,
                'articles_count' => 10
            ],
            'tutoriais' => [
                'id' => 222222,
                'handle' => 'tutoriais',
                'title' => 'Tutoriais e Dicas',
                'url' => '/blogs/tutoriais',
                'commentable' => 'moderate',
                'comments_enabled' => true,
                'moderated' => true,
                'articles_count' => 5
            ]
        ];
    }
    
    private function getArticleData(): array
    {
        return [
            'id' => 12345,
            'handle' => 'tendencias-de-moda-para-o-verao',
            'title' => 'Tendências de Moda para o Verão 2025',
            'author' => 'Maria Oliveira',
            'content' => '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed euismod, urna eu tincidunt consectetur, ipsum nunc euismod nisi, eu aliquam nisl nunc eu nisi. Sed euismod, urna eu tincidunt consectetur, ipsum nunc euismod nisi, eu aliquam nisl nunc eu nisi.</p><p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed euismod, urna eu tincidunt consectetur, ipsum nunc euismod nisi, eu aliquam nisl nunc eu nisi. Sed euismod, urna eu tincidunt consectetur, ipsum nunc euismod nisi, eu aliquam nisl nunc eu nisi.</p>',
            'excerpt' => 'Descubra as principais tendências de moda para o verão 2025. Cores vibrantes, estampas e tecidos leves são alguns dos destaques.',
            'excerpt_or_content' => 'Descubra as principais tendências de moda para o verão 2025. Cores vibrantes, estampas e tecidos leves são alguns dos destaques.',
            'image' => [
                'src' => 'https://cdn.shopify.com/s/files/1/0000/0000/0000/articles/tendencias-verao.jpg',
                'alt' => 'Tendências de Verão 2025',
                'width' => 1200,
                'height' => 800,
                'aspect_ratio' => 1.5
            ],
            'featured_media' => [
                'id' => 12345,
                'position' => 1,
                'alt' => 'Tendências de Verão 2025',
                'aspect_ratio' => 1.5,
                'height' => 800,
                'width' => 1200,
                'media_type' => 'image',
                'preview_image' => [
                    'src' => 'https://cdn.shopify.com/s/files/1/0000/0000/0000/articles/tendencias-verao.jpg',
                    'alt' => 'Tendências de Verão 2025',
                    'width' => 1200,
                    'height' => 800
                ]
            ],
            'published_at' => date('Y-m-d\TH:i:s', strtotime('-1 week')),
            'created_at' => date('Y-m-d\TH:i:s', strtotime('-2 weeks')),
            'updated_at' => date('Y-m-d\TH:i:s', strtotime('-1 day')),
            'tags' => ['moda', 'tendências', 'verão', 'estilo'],
            'comments_count' => 5,
            'comments' => [
                [
                    'id' => 54321,
                    'author' => 'João Silva',
                    'email' => 'joao.silva@example.com',
                    'content' => 'Ótimo artigo! Muito informativo.',
                    'status' => 'published',
                    'created_at' => date('Y-m-d\TH:i:s', strtotime('-5 days'))
                ],
                [
                    'id' => 54322,
                    'author' => 'Ana Costa',
                    'email' => 'ana.costa@example.com',
                    'content' => 'Adorei as dicas de estampas!',
                    'status' => 'published',
                    'created_at' => date('Y-m-d\TH:i:s', strtotime('-3 days'))
                ]
            ],
            'comment_post_url' => '/blogs/noticias/tendencias-de-moda-para-o-verao/comments',
            'url' => '/blogs/noticias/tendencias-de-moda-para-o-verao',
            'metafields' => [],
            'moderated' => true,
            'user' => [
                'name' => 'Maria Oliveira',
                'email' => 'maria@example.com',
                'bio' => 'Especialista em moda e tendências'
            ],
            'next_article' => [
                'title' => 'Como Montar Looks para o Escritório',
                'url' => '/blogs/noticias/como-montar-looks-para-o-escritorio',
                'published_at' => date('Y-m-d\TH:i:s', strtotime('-3 days'))
            ],
            'previous_article' => [
                'title' => 'Guia de Cuidados com Roupas de Inverno',
                'url' => '/blogs/noticias/guia-de-cuidados-com-roupas-de-inverno',
                'published_at' => date('Y-m-d\TH:i:s', strtotime('-2 weeks'))
            ]
        ];
    }
    
    private function getArticlesData(): array
    {
        return [
            [
                'id' => 12345,
                'handle' => 'tendencias-de-moda-para-o-verao',
                'title' => 'Tendências de Moda para o Verão 2025',
                'author' => 'Maria Oliveira',
                'excerpt' => 'Descubra as principais tendências de moda para o verão 2025. Cores vibrantes, estampas e tecidos leves são alguns dos destaques.',
                'image' => [
                    'src' => 'https://cdn.shopify.com/s/files/1/0000/0000/0000/articles/tendencias-verao.jpg',
                    'alt' => 'Tendências de Verão 2025'
                ],
                'published_at' => date('Y-m-d\TH:i:s', strtotime('-1 week')),
                'tags' => ['moda', 'tendências', 'verão', 'estilo'],
                'comments_count' => 5,
                'url' => '/blogs/noticias/tendencias-de-moda-para-o-verao'
            ],
            [
                'id' => 12346,
                'handle' => 'como-montar-looks-para-o-escritorio',
                'title' => 'Como Montar Looks para o Escritório',
                'author' => 'Maria Oliveira',
                'excerpt' => 'Dicas práticas para montar looks elegantes e confortáveis para o ambiente de trabalho.',
                'image' => [
                    'src' => 'https://cdn.shopify.com/s/files/1/0000/0000/0000/articles/looks-escritorio.jpg',
                    'alt' => 'Looks para o Escritório'
                ],
                'published_at' => date('Y-m-d\TH:i:s', strtotime('-3 days')),
                'tags' => ['moda', 'escritório', 'trabalho', 'estilo'],
                'comments_count' => 3,
                'url' => '/blogs/noticias/como-montar-looks-para-o-escritorio'
            ],
            [
                'id' => 12347,
                'handle' => 'guia-de-cuidados-com-roupas-de-inverno',
                'title' => 'Guia de Cuidados com Roupas de Inverno',
                'author' => 'Carlos Santos',
                'excerpt' => 'Aprenda a cuidar corretamente das suas roupas de inverno para mantê-las em bom estado por muito mais tempo.',
                'image' => [
                    'src' => 'https://cdn.shopify.com/s/files/1/0000/0000/0000/articles/cuidados-roupas-inverno.jpg',
                    'alt' => 'Cuidados com Roupas de Inverno'
                ],
                'published_at' => date('Y-m-d\TH:i:s', strtotime('-2 weeks')),
                'tags' => ['cuidados', 'inverno', 'roupas', 'dicas'],
                'comments_count' => 7,
                'url' => '/blogs/noticias/guia-de-cuidados-com-roupas-de-inverno'
            ]
        ];
    }
}