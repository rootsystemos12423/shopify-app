<?php

namespace App\Liquid\CustomTags;

use Liquid\Tag\TagBlock;
use Liquid\Context;
use Illuminate\Support\Facades\Log;

/**
 * Custom Liquid tag for wrapping content in <style>…</style> blocks.
 * Processes Shopify-style theme settings and variables.
 */
class StyleTag extends TagBlock
{
    /**
     * Define the tag name: {% style %} ... {% endstyle %}
     */
    public static function tagName(): string
    {
        return 'style';
    }

    /**
     * Render the inner content and wrap it in a <style> tag.
     *
     * @param Context $context
     * @return string
     */
    public function render(Context $context): string
    {
        try {
            // Process color schemes to ensure they are strings before rendering
            $this->preprocessColorSchemes($context);
            
            // Renderiza o conteúdo do bloco (o engine Liquid já processa as funções)
            $cssContent = $this->renderAll($this->nodelist, $context);
            
            // Apenas corrige problemas de formatação CSS
            $cssContent = $this->fixCssFormatting($cssContent);
            
            // Processa referências de fontes
            $cssContent = $this->processFontReferences($cssContent, $context);
            
            return "<style>\n{$cssContent}\n</style>";
        } catch (\Exception $e) {
            Log::error('Error rendering style tag: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return "<!-- Error rendering style tag: " . htmlspecialchars($e->getMessage()) . " -->";
        }
    }
    
    /**
     * Preprocess color schemes to ensure they can be appended to strings
     */
    private function preprocessColorSchemes(Context $context): void
    {
        // Get color_schemes from context
        $colorSchemes = $context->get('color_schemes');
        if (!is_array($colorSchemes)) {
            return;
        }
        
        // Create a new array of color schemes with string values for the IDs
        $processedSchemes = [];
        foreach ($colorSchemes as $key => $scheme) {
            if (!isset($scheme['id'])) {
                continue;
            }
            
            // Create a new scheme with a __toString method or value
            $processedScheme = $scheme;
            // Add a string representation
            $processedScheme['__string_value'] = $scheme['id'];
            $processedSchemes[$key] = $processedScheme;
        }
        
        // Store the processed schemes back in context
        $context->set('color_schemes', $processedSchemes);
        
        // Also process scheme_classes for the same reason
        $schemeClasses = $context->get('scheme_classes');
        if (is_array($schemeClasses)) {
            $processedClasses = [];
            foreach ($schemeClasses as $key => $value) {
                // Convert to simple string values if needed
                $processedClasses[$key] = (string)$value;
            }
            $context->set('scheme_classes', $processedClasses);
        }
    }

    public function color_brightness($input)
    {
        if (empty($input)) {
            Log::debug('color_brightness: empty input');
            return 0;
        }
        
        // Garantir que temos um array ou objeto
        if (is_string($input) && strpos($input, '{') === 0) {
            // Se for uma string JSON, converter para array
            $input = json_decode($input, true);
        }
        
        Log::debug('color_brightness input:', [
            'type' => gettype($input),
            'input' => $input
        ]);
        
        // Handle string hex colors
        if (is_string($input) && strpos($input, '#') === 0) {
            $rgb = $this->hexToRgb($input);
            $r = $rgb['r'];
            $g = $rgb['g'];
            $b = $rgb['b'];
        }
        // Handle array with RGB values  
        elseif (is_array($input) && isset($input['red'], $input['green'], $input['blue'])) {
            $r = $input['red'];
            $g = $input['green'];
            $b = $input['blue'];
        }
        // Handle array with r, g, b properties (from hexToRgb)
        elseif (is_array($input) && isset($input['r'], $input['g'], $input['b'])) {
            $r = $input['r'];
            $g = $input['g'];
            $b = $input['b'];
        }
        // Handle object with red, green, blue properties
        elseif (is_object($input) && isset($input->red, $input->green, $input->blue)) {
            $r = $input->red;
            $g = $input->green;
            $b = $input->blue;
        }
        // Handle object with r, g, b properties
        elseif (is_object($input) && isset($input->r, $input->g, $input->b)) {
            $r = $input->r;
            $g = $input->g;
            $b = $input->b;
        }
        else {
            Log::debug('color_brightness: unknown format', ['input' => $input]);
            return 0;
        }
        
        return round(($r * 299 + $g * 587 + $b * 114) / 1000);
    }

    public function color_lighten($input, $percentage)
    {
        // Garantir que $input tenha um formato válido
        if (empty($input)) {
            return (object)[
                'red' => 64,
                'green' => 64,
                'blue' => 64,
                'rgb' => '64, 64, 64',
                'hex' => '#404040'
            ];
        }
        
        // Se o input for um objeto, converter para array
        if (is_object($input)) {
            $input = (array)$input;
        }
        
        // Se for string JSON, converter
        if (is_string($input) && strpos($input, '{') === 0) {
            $input = json_decode($input, true);
        }
        
        // Processar hex ou rgb
        if (is_string($input) && strpos($input, '#') === 0) {
            $rgb = $this->hexToRgb($input);
            $r = $rgb['r'];
            $g = $rgb['g'];
            $b = $rgb['b'];
        }
        // Verificar estruturas RGB diferentes
        elseif (is_array($input) && isset($input['red'], $input['green'], $input['blue'])) {
            $r = $input['red'];
            $g = $input['green'];
            $b = $input['blue'];
        }
        elseif (is_array($input) && isset($input['r'], $input['g'], $input['b'])) {
            $r = $input['r'];
            $g = $input['g'];
            $b = $input['b'];
        }
        // Fallback padrão
        else {
            Log::warning('color_lighten: formato inválido', [
                'input' => $input,
                'type' => gettype($input)
            ]);
            return (object)[
                'red' => 64,
                'green' => 64,
                'blue' => 64,
                'rgb' => '64, 64, 64',
                'hex' => '#404040'
            ];
        }
        
        $percentage = (float)$percentage;
        
        $r = min(255, $r + round(($percentage / 100) * (255 - $r)));
        $g = min(255, $g + round(($percentage / 100) * (255 - $g)));
        $b = min(255, $b + round(($percentage / 100) * (255 - $b)));
        
        // Retornar SEMPRE um objeto válido
        return (object)[
            'red' => $r,
            'green' => $g,
            'blue' => $b,
            'rgb' => "{$r}, {$g}, {$b}", // Adicionar espaços após vírgulas
            'hex' => sprintf("#%02x%02x%02x", $r, $g, $b)
        ];
    }

    public function color_darken($input, $percentage)
    {
        // Similar ao color_lighten, mas com a lógica de escurecer
        if (empty($input)) {
            return (object)[
                'red' => 0,
                'green' => 0,
                'blue' => 0,
                'rgb' => '0, 0, 0',
                'hex' => '#000000'
            ];
        }
        
        // Se o input for um objeto, converter para array
        if (is_object($input)) {
            $input = (array)$input;
        }
        
        // Se for string JSON, converter
        if (is_string($input) && strpos($input, '{') === 0) {
            $input = json_decode($input, true);
        }
        
        // Processar hex ou rgb
        if (is_string($input) && strpos($input, '#') === 0) {
            $rgb = $this->hexToRgb($input);
            $r = $rgb['r'];
            $g = $rgb['g'];
            $b = $rgb['b'];
        }
        // Verificar estruturas RGB diferentes
        elseif (is_array($input) && isset($input['red'], $input['green'], $input['blue'])) {
            $r = $input['red'];
            $g = $input['green'];
            $b = $input['blue'];
        }
        elseif (is_array($input) && isset($input['r'], $input['g'], $input['b'])) {
            $r = $input['r'];
            $g = $input['g'];
            $b = $input['b'];
        }
        // Fallback padrão
        else {
            Log::warning('color_darken: formato inválido', [
                'input' => $input,
                'type' => gettype($input)
            ]);
            return (object)[
                'red' => 0,
                'green' => 0,
                'blue' => 0,
                'rgb' => '0, 0, 0',
                'hex' => '#000000'
            ];
        }
        
        $percentage = (float)$percentage;
        
        $r = max(0, $r - round(($percentage / 100) * $r));
        $g = max(0, $g - round(($percentage / 100) * $g));
        $b = max(0, $b - round(($percentage / 100) * $b));
        
        // Retornar SEMPRE um objeto válido
        return (object)[
            'red' => $r,
            'green' => $g,
            'blue' => $b,
            'rgb' => "{$r}, {$g}, {$b}", // Adicionar espaços após vírgulas
            'hex' => sprintf("#%02x%02x%02x", $r, $g, $b)
        ];
    }

    public function to_hex($input)
    {
        if (is_string($input) && strpos($input, '#') === 0) {
            return $input;
        }
        
        if (is_array($input) && isset($input['red'], $input['green'], $input['blue'])) {
            return sprintf("#%02x%02x%02x", $input['red'], $input['green'], $input['blue']);
        }
        
        if (is_object($input) && isset($input->red, $input->green, $input->blue)) {
            return sprintf("#%02x%02x%02x", $input->red, $input->green, $input->blue);
        }
        
        return '#000000';
    }

    /**
     * Corrige problemas comuns de formatação CSS
     */
    private function fixCssFormatting(string $css): string
    {
        // Corrigir variáveis de cor malformadas
        $css = preg_replace_callback(
            '/--color-[^:]+:\s*,,;/m',
            function($match) {
                // Se encontrar ",," vazio, substituir por "0, 0, 0"
                return str_replace(',,;', '0, 0, 0;', $match[0]);
            },
            $css
        );
        
        // Fix string representation of arrays in CSS
        $css = preg_replace_callback(
            '/:\s*\[[^\]]*\];/',
            function($match) {
                return ': transparent;';
            },
            $css
        );
        
        // Fix JSON objects in CSS
        $css = preg_replace_callback(
            '/:\s*\{[^}]*\};/',
            function($match) {
                return ': transparent;';
            },
            $css
        );
        
        // Corrigir gradient background vazio
        $css = preg_replace_callback(
            '/--gradient-background:\s*;/m',
            function($match) {
                // Se estiver vazio, adicionar um fallback
                return '--gradient-background: transparent;';
            },
            $css
        );
        
        return $css;
    }
    
    /**
     * Processa referências a fontes no CSS
     */
    private function processFontReferences(string $css, Context $context): string
    {
        // Verificar se já existem fontes definidas no contexto
        $bodyFontModified = $context->get('body_font_bold');
        
        if (!$bodyFontModified) {
            // Criar variantes de fontes
            $this->createFontVariants($context);
        }
        
        return $css;
    }
    
    /**
     * Cria variantes de fontes no contexto
     */
    private function createFontVariants(Context $context): void
    {
        $settings = $context->get('settings');
        if (!$settings) return;
        
        // Fonte do corpo
        $bodyFont = $settings['type_body_font'] ?? null;
        if ($bodyFont) {
            if (is_string($bodyFont)) {
                // Tenta extrair informações da fonte de uma string (ex: "assistant_n4")
                $parts = explode('_', $bodyFont);
                $family = $parts[0];
                $weight = isset($parts[1]) && substr($parts[1], 0, 1) === 'n' ? 
                    intval(substr($parts[1], 1)) : 400;
                
                $bodyFont = [
                    'family' => $family,
                    'fallback_families' => 'sans-serif',
                    'weight' => $weight,
                    'style' => 'normal'
                ];
            }
            
            $weight = $bodyFont['weight'] ?? 400;
            $boldWeight = min(($weight + 300), 900);
            
            // Fonte negrito
            $bodyFontBold = array_merge($bodyFont, [
                'weight' => $boldWeight,
                'style' => 'normal'
            ]);
            $context->set('body_font_bold', $bodyFontBold);
            
            // Fonte itálico
            $bodyFontItalic = array_merge($bodyFont, [
                'style' => 'italic'
            ]);
            $context->set('body_font_italic', $bodyFontItalic);
            
            // Fonte negrito itálico
            $bodyFontBoldItalic = array_merge($bodyFont, [
                'weight' => $boldWeight,
                'style' => 'italic'
            ]);
            $context->set('body_font_bold_italic', $bodyFontBoldItalic);
        }
        
        // Fonte do cabeçalho
        $headerFont = $settings['type_header_font'] ?? null;
        if ($headerFont) {
            if (is_string($headerFont)) {
                // Tenta extrair informações da fonte de uma string
                $parts = explode('_', $headerFont);
                $family = $parts[0];
                $weight = isset($parts[1]) && substr($parts[1], 0, 1) === 'n' ? 
                    intval(substr($parts[1], 1)) : 400;
                
                $headerFont = [
                    'family' => $family,
                    'fallback_families' => 'sans-serif',
                    'weight' => $weight,
                    'style' => 'normal'
                ];
            }
            
            $context->set('heading_font', $headerFont);
        }
    }
    
    /**
     * Converte cor HEX para RGB
     */
    private function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        
        if (strlen($hex) === 3) {
            $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
            $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
            $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }
        
        return [
            'r' => $r,
            'g' => $g,
            'b' => $b
        ];
    }
}