<?php

namespace App\Services;

use Liquid\Context;

class ThemeProvider implements ContextProvider
{
    public function provide(Context $context, array $params): void
    {
        $theme = $params['theme'] ?? null;
        
        if (!$theme) {
            return;
        }
        
        $themeData = [
            'id' => $theme->shopify_theme_id,
            'name' => $theme->name,
            'role' => $theme->role,
            'store_id' => $theme->store_id,
            'handle' => $this->getThemeHandle($theme->name),
            'settings' => $theme->settings ?? [],
            'description' => $theme->description ?? '',
            'published_at' => date('Y-m-d\TH:i:s'),
            'theme_store_id' => null,
            'directory' => 'themes/' . $theme->shopify_theme_id,
            'version' => '1.0.0',
            'source' => 'custom',
            'tags' => ['customizado', 'responsivo', 'moderna'],
            'author' => [
                'name' => 'Desenvolvedor',
                'email' => 'dev@example.com'
            ],
            'cache_settings' => [
                'cache_buster' => time(),
                'cache_expires' => date('Y-m-d\TH:i:s', strtotime('+1 day'))
            ]
        ];
        
        $context->set('theme', $themeData);
    }
    
    private function getThemeHandle(string $themeName): string
    {
        return strtolower(str_replace(' ', '-', $themeName));
    }
}