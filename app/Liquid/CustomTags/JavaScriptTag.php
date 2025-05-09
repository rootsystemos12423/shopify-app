<?php

namespace App\Liquid\CustomTags;

use Liquid\AbstractTag;
use Liquid\Context;
use Liquid\FileSystem;
use Liquid\Regexp;
use Illuminate\Support\Facades\Log;

/**
 * Tag para renderizar blocos de JavaScript em templates Liquid
 * 
 * Uso:
 * {% javascript %}
 *   // Código JavaScript aqui
 * {% endjavascript %}
 */
class JavaScriptTag extends AbstractTag
{
    /**
     * Conteúdo JavaScript a ser renderizado
     */
    protected $jsContent = '';
    
    /**
     * Nome da tag
     */
    public static function tagName(): string
    {
        return 'javascript';
    }

    /**
     * Constructor que captura todo o conteúdo entre as tags
     */
    public function __construct($markup, array &$tokens, FileSystem $fileSystem = null)
    {
        parent::__construct($markup, $tokens, $fileSystem);
        
        // Extrair o conteúdo JavaScript entre as tags
        $this->jsContent = $this->extractJavaScriptContent($tokens);
    }
    
    /**
     * Extrai o conteúdo JavaScript dos tokens até encontrar endjavascript
     */
    private function extractJavaScriptContent(array &$tokens): string
    {
        $content = '';
        $depth = 1;
        
        while (count($tokens) > 0) {
            $token = array_shift($tokens);
            
            // Verificar tag de fechamento
            if (preg_match('/\{%\s*endjavascript\s*%\}/', $token)) {
                $depth--;
                if ($depth === 0) {
                    break;
                }
            }
            
            // Verificar tags aninhadas (embora não devam ser usadas, por precaução)
            if (preg_match('/\{%\s*javascript\s*%\}/', $token)) {
                $depth++;
            }
            
            // Acumular conteúdo
            if ($depth > 0) {
                $content .= $token;
            }
        }
        
        return $content;
    }

    /**
     * Renderiza o JavaScript em uma tag <script>
     */
    public function render(Context $context): string
    {
        try {
            // Processar variáveis do Liquid dentro do JavaScript
            $processedJs = $this->processLiquidVariables($this->jsContent, $context);
            
            // Retorna o JavaScript envolvido em uma tag script
            return $this->wrapInScriptTag($processedJs);
        } catch (\Exception $e) {
            // Log de erro e retornar comentário para debugging
            if (class_exists('Illuminate\Support\Facades\Log')) {
                Log::error('Erro ao renderizar JavaScript:', [
                    'erro' => $e->getMessage(),
                    'conteudo' => $this->jsContent
                ]);
            }
            
            return "<!-- Erro ao processar JavaScript: " . htmlspecialchars($e->getMessage()) . " -->";
        }
    }
    
    /**
     * Processa variáveis Liquid dentro do JavaScript
     */
    private function processLiquidVariables(string $content, Context $context): string
    {
        // Procurar por expressões Liquid {{ variable }}
        $regexp = new Regexp('/\{\{\s*([^}]+)\s*\}\}/');
        
        return preg_replace_callback('/\{\{\s*([^}]+)\s*\}\}/', function($matches) use ($context) {
            $variable = trim($matches[1]);
            $value = $context->get($variable);
            
            // Converter para JSON se for array ou objeto
            if (is_array($value) || is_object($value)) {
                return json_encode($value);
            }
            
            // Escapar strings
            if (is_string($value)) {
                return '"' . addslashes($value) . '"';
            }
            
            // Retornar outros valores diretamente
            return $value;
        }, $content);
    }
    
    /**
     * Envolve o código JavaScript em uma tag <script>
     */
    private function wrapInScriptTag(string $jsContent): string
    {
        return '<script>' . PHP_EOL . $jsContent . PHP_EOL . '</script>';
    }
}