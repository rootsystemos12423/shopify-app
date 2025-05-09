<?php

namespace App\Services;

use Liquid\Context;

class TemplateProvider implements ContextProvider
{
    public function provide(Context $context, array $params): void
    {
        $templateName = $params['template'] ?? 'index';
        
        $context->set('template', [
            'name' => $templateName,
            'directory' => $this->getTemplateDirectory($templateName),
            'suffix' => '',
            'language' => 'pt-BR'
        ]);
        
        // Set template-specific variables
        $this->setTemplateVariables($context, $templateName);
    }
    
    private function getTemplateDirectory(string $templateName): string
    {
        if (strpos($templateName, 'customers/') === 0) {
            return 'customers';
        }
        
        $map = [
            'index' => '',
            'product' => 'products',
            'collection' => 'collections',
            'cart' => '',
            'page' => 'pages',
            'blog' => 'blogs',
            'article' => 'articles',
            'search' => ''
        ];
        
        return $map[$templateName] ?? 'templates';
    }
    
    private function setTemplateVariables(Context $context, string $templateName): void
    {
        switch ($templateName) {
            case 'index':
                $context->set('is_index', true);
                break;
                
            case 'product':
                $context->set('is_product', true);
                break;
                
            case 'collection':
                $context->set('is_collection', true);
                break;
                
            case 'cart':
                $context->set('is_cart', true);
                break;
                
            case 'page':
                $context->set('is_page', true);
                break;
                
            case 'blog':
                $context->set('is_blog', true);
                break;
                
            case 'article':
                $context->set('is_article', true);
                break;
                
            case 'search':
                $context->set('is_search', true);
                break;
                
            case 'customers/login':
            case 'customers/register':
            case 'customers/account':
            case 'customers/order':
            case 'customers/addresses':
            case 'customers/reset_password':
            case 'customers/activate_account':
                $context->set('is_customer_page', true);
                break;
        }
    }
}