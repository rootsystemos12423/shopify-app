<?php

namespace App\Services;

use Liquid\Context;

class ArticleProvider implements ContextProvider
{
    public function provide(Context $context, array $params): void
    {
        // Adiciona artigo atual se estamos em uma página de artigo
        if ($params['template'] === 'article') {
            $context->set('article', $this->getArticleData());
        }
    }
    
    private function getArticleData(): array
    {
        return [
            'id' => 123456,
            'title' => 'Tendências de Moda para o Verão 2025',
            'author' => 'Maria Oliveira',
            'content' => '<p>O verão 2025 promete ser repleto de cores vibrantes e tecidos sustentáveis. Neste artigo, vamos explorar as principais tendências que vão dominar a estação mais quente do ano.</p><h2>Cores Vibrantes</h2><p>O amarelo e o laranja serão as cores do verão 2025, trazendo alegria e energia para as produções. Tons terrosos também continuam em alta, especialmente em combinações com as cores vibrantes.</p><h2>Tecidos Sustentáveis</h2><p>A preocupação com o meio ambiente segue influenciando fortemente a moda, com tecidos sustentáveis ganhando cada vez mais espaço nas coleções de grandes marcas.</p><h2>Estampas</h2><p>Estampas florais em tamanhos maiores e com cores contrastantes estarão em alta, assim como os padrões geométricos abstratos.</p>',
            'excerpt' => 'Descubra as principais tendências de moda para o verão 2025, com destaque para cores vibrantes, tecidos sustentáveis e estampas marcantes.',
            'handle' => 'tendencias-de-moda-para-o-verao-2025',
            'image' => [
                'src' => 'https://cdn.shopify.com/s/files/1/0000/0000/0000/articles/tendencias-verao-2025.jpg',
                'alt' => 'Tendências de Moda para o Verão 2025',
                'width' => 1200,
                'height' => 800,
                'aspect_ratio' => 1.5
            ],
            'published_at' => date('Y-m-d\TH:i:s', strtotime('-1 week')),
            'created_at' => date('Y-m-d\TH:i:s', strtotime('-10 days')),
            'updated_at' => date('Y-m-d\TH:i:s', strtotime('-2 days')),
            'comments' => $this->getArticleComments(),
            'comments_count' => 3,
            'comment_post_url' => '/blogs/noticias/tendencias-de-moda-para-o-verao-2025/comments',
            'moderated' => true,
            'tags' => ['moda', 'tendências', 'verão', 'sustentabilidade'],
            'url' => '/blogs/noticias/tendencias-de-moda-para-o-verao-2025',
            'user' => [
                'name' => 'Maria Oliveira',
                'email' => 'maria@example.com',
                'bio' => 'Especialista em moda e tendências, com mais de 10 anos de experiência no setor.',
                'avatar' => 'https://cdn.shopify.com/s/files/1/0000/0000/0000/files/maria-avatar.jpg'
            ],
            'metafields' => [
                'global' => [
                    'title_tag' => 'Tendências de Moda para o Verão 2025 | Blog da Loja Demo',
                    'description_tag' => 'Descubra as principais tendências de moda para o verão 2025. Cores vibrantes, tecidos sustentáveis e estampas marcantes em destaque.'
                ]
            ],
            'template_suffix' => '',
            'blog_id' => 11111,
            'blog' => [
                'id' => 11111,
                'handle' => 'noticias',
                'title' => 'Notícias e Novidades',
                'url' => '/blogs/noticias'
            ],
            'next_article' => [
                'title' => 'Guia Completo de Cuidados com Roupas',
                'url' => '/blogs/noticias/guia-completo-de-cuidados-com-roupas',
                'published_at' => date('Y-m-d\TH:i:s', strtotime('-4 days'))
            ],
            'previous_article' => [
                'title' => 'Peças Essenciais para o Guarda-roupa Masculino',
                'url' => '/blogs/noticias/pecas-essenciais-para-o-guarda-roupa-masculino',
                'published_at' => date('Y-m-d\TH:i:s', strtotime('-2 weeks'))
            ]
        ];
    }
    
    private function getArticleComments(): array
    {
        return [
            [
                'id' => 98765,
                'author' => 'João Silva',
                'email' => 'joao.silva@example.com',
                'content' => 'Adorei o artigo! Muito informativo e útil para quem, como eu, gosta de estar sempre atualizado com as tendências.',
                'status' => 'published',
                'created_at' => date('Y-m-d\TH:i:s', strtotime('-5 days'))
            ],
            [
                'id' => 98766,
                'author' => 'Ana Costa',
                'email' => 'ana.costa@example.com',
                'content' => 'Interessante saber sobre os tecidos sustentáveis. Estou cada vez mais consciente sobre meu consumo de moda.',
                'status' => 'published',
                'created_at' => date('Y-m-d\TH:i:s', strtotime('-3 days'))
            ],
            [
                'id' => 98767,
                'author' => 'Carlos Souza',
                'email' => 'carlos.souza@example.com',
                'content' => 'As cores desse verão parecem muito interessantes! Já estou ansioso para renovar meu guarda-roupa.',
                'status' => 'published',
                'created_at' => date('Y-m-d\TH:i:s', strtotime('-1 day'))
            ]
        ];
    }
}