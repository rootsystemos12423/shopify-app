<?php

namespace App\Services;

use Liquid\Context;

class PageProvider implements ContextProvider
{
    public function provide(Context $context, array $params): void
    {
        // Adiciona dados de página somente se estamos em uma página de conteúdo
        if ($params['template'] === 'page') {
            $context->set('page', $this->getPageData());
        }
        
        // Disponibiliza todas as páginas para acesso global
        $context->set('pages', $this->getAllPages());
    }
    
    private function getPageData(): array
    {
        return [
            'id' => 12345,
            'handle' => 'sobre-nos',
            'title' => 'Sobre Nós',
            'content' => '<h1>Nossa História</h1><p>Somos uma empresa dedicada a oferecer produtos de qualidade com excelente custo-benefício. Fundada em 2010, nossa loja trabalha com as melhores marcas do mercado.</p><h2>Missão</h2><p>Proporcionar a melhor experiência de compra para nossos clientes, com produtos de qualidade e atendimento excepcional.</p><h2>Valores</h2><ul><li>Transparência</li><li>Qualidade</li><li>Ética</li><li>Compromisso com o cliente</li></ul>',
            'published_at' => date('Y-m-d\TH:i:s', strtotime('-1 year')),
            'created_at' => date('Y-m-d\TH:i:s', strtotime('-1 year')),
            'updated_at' => date('Y-m-d\TH:i:s', strtotime('-1 month')),
            'url' => '/pages/sobre-nos',
            'author' => 'Admin',
            'template_suffix' => '',
            'metafields' => [
                'global' => [
                    'title_tag' => 'Sobre Nós - Conheça Nossa História | Loja Demo',
                    'description_tag' => 'Conheça a história, missão e valores da Loja Demo, uma empresa comprometida com a qualidade e satisfação do cliente.'
                ]
            ]
        ];
    }
    
    private function getAllPages(): array
    {
        return [
            'sobre-nos' => [
                'id' => 12345,
                'handle' => 'sobre-nos',
                'title' => 'Sobre Nós',
                'url' => '/pages/sobre-nos'
            ],
            'contato' => [
               'id' => 12346,
                'handle' => 'contato',
                'title' => 'Contato',
                'url' => '/pages/contato'
            ],
            'perguntas-frequentes' => [
                'id' => 12347,
                'handle' => 'perguntas-frequentes',
                'title' => 'Perguntas Frequentes',
                'url' => '/pages/perguntas-frequentes'
            ],
            'termos-de-servico' => [
                'id' => 12348,
                'handle' => 'termos-de-servico',
                'title' => 'Termos de Serviço',
                'url' => '/pages/termos-de-servico'
            ],
            'politica-de-privacidade' => [
                'id' => 12349,
                'handle' => 'politica-de-privacidade',
                'title' => 'Política de Privacidade',
                'url' => '/pages/politica-de-privacidade'
            ]
        ];
    }
}