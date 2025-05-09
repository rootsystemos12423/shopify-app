<?php

namespace App\Services;

use Liquid\Context;

class RequestProvider implements ContextProvider
{
    public function provide(Context $context, array $params): void
    {
        $request = $params['request'] ?? request();
        
        $context->set('request', [
            'locale' => [
                'iso_code' => 'pt-BR',
                'language' => 'pt',
                'endonym_name' => 'Português (Brasil)',
                'root_url' => '/'
            ],
            'page_type' => $params['template'] ?? 'index',
            'design_mode' => false,
            'host' => $request->getHost(),
            'path' => $request->path(),
            'origin' => $request->headers->get('origin', ''),
            'url' => $request->url(),
            'query_string' => $request->getQueryString() ?? '',
            'params' => $request->route()?->parameters() ?? [],
            'controller' => $this->getControllerName($request),
            'robots' => [
                'index' => true,
                'follow' => true,
                'noindex' => false,
                'nofollow' => false
            ],
            'device' => [
                'browser' => $this->getBrowserInfo($request),
                'device_type' => $this->getDeviceType($request),
                'is_bot' => false,
                'is_mobile' => $this->isMobile($request),
                'os' => $this->getOS($request)
            ]
        ]);
    }
    
    private function getControllerName($request): string
    {
        $path = $request->path();
        
        if (empty($path) || $path === '/') {
            return 'index';
        }
        
        $segments = explode('/', $path);
        return $segments[0];
    }
    
    private function getBrowserInfo($request): array
    {
        $userAgent = $request->header('User-Agent', '');
        
        // Detecção básica de navegador
        $browser = 'unknown';
        $version = '';
        
        if (strpos($userAgent, 'Chrome') !== false) {
            $browser = 'chrome';
        } elseif (strpos($userAgent, 'Firefox') !== false) {
            $browser = 'firefox';
        } elseif (strpos($userAgent, 'Safari') !== false) {
            $browser = 'safari';
        } elseif (strpos($userAgent, 'Edge') !== false) {
            $browser = 'edge';
        } elseif (strpos($userAgent, 'MSIE') !== false || strpos($userAgent, 'Trident') !== false) {
            $browser = 'ie';
        }
        
        return [
            'name' => $browser,
            'version' => $version,
            'user_agent' => $userAgent
        ];
    }
    
    private function getDeviceType($request): string
    {
        $userAgent = $request->header('User-Agent', '');
        
        if (strpos($userAgent, 'Mobile') !== false) {
            return 'mobile';
        } elseif (strpos($userAgent, 'Tablet') !== false) {
            return 'tablet';
        }
        
        return 'desktop';
    }
    
    private function isMobile($request): bool
    {
        $userAgent = $request->header('User-Agent', '');
        return strpos($userAgent, 'Mobile') !== false;
    }
    
    private function getOS($request): string
    {
        $userAgent = $request->header('User-Agent', '');
        
        if (strpos($userAgent, 'Windows') !== false) {
            return 'windows';
        } elseif (strpos($userAgent, 'Macintosh') !== false) {
            return 'mac';
        } elseif (strpos($userAgent, 'Linux') !== false) {
            return 'linux';
        } elseif (strpos($userAgent, 'Android') !== false) {
            return 'android';
        } elseif (strpos($userAgent, 'iOS') !== false || strpos($userAgent, 'iPhone') !== false || strpos($userAgent, 'iPad') !== false) {
            return 'ios';
        }
        
        return 'unknown';
    }
}