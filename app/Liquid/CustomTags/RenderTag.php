<?php

namespace App\Liquid\CustomTags;

use Liquid\AbstractTag;
use Liquid\Context;
use Liquid\Exception\ParseException;
use Liquid\FileSystem;
use Liquid\Liquid;
use Liquid\Regexp;
use Liquid\Template;
use Liquid\Document;
use Illuminate\Support\Facades\Log;

class RenderTag extends AbstractTag
{
    /**
     * @var string The name of the snippet
     */
    protected $snippetName;

    /**
     * @var bool True if the variable is a collection
     */
    protected $collection;

    /**
     * @var mixed The value to pass to the child template
     */
    protected $variable;

    /**
     * @var Document The Document that represents the included template
     */
    protected $document;
    
    /**
     * @var array Atributos extraídos do markup
     */
    protected $attributes = [];
    
    /**
     * @var bool Whether we're rendering a block object directly
     */
    protected $isBlockRendering = false;

    public static function tagName(): string
    {
        return 'render';
    }

    public function __construct($markup, array &$tokens, FileSystem $fileSystem = null)
    {
        $regex = new Regexp('/("[^"]+"|\'[^\']+\'|[^\'"\s]+)(\s+(with|for)\s+(' . Liquid::get('QUOTED_FRAGMENT') . '+))?/');

        if (!$regex->match($markup)) {
            throw new ParseException("Error in tag 'render' - Valid syntax: render '[snippet]' (with|for) [object|collection]");
        }

        $unquoted = strpos($regex->matches[1], '"') === false && strpos($regex->matches[1], "'") === false;

        $start = 1;
        $len = strlen($regex->matches[1]) - 2;

        if ($unquoted) {
            $start = 0;
            $len = strlen($regex->matches[1]);
            
            // Special case for app blocks: {% render block %}
            if ($regex->matches[1] === 'block') {
                $this->isBlockRendering = true;
                $this->snippetName = 'block'; // Just save the name for reference
                $this->extractAttributes($markup);
                parent::__construct($markup, $tokens, $fileSystem);
                return;
            }
        }

        // Regular path for normal snippets - add 'snippets/' prefix
        $this->snippetName = 'snippets/' . ltrim(substr($regex->matches[1], $start, $len), '_');

        if (isset($regex->matches[1])) {
            $this->collection = (isset($regex->matches[3])) ? ($regex->matches[3] == "for") : null;
            $this->variable = (isset($regex->matches[4])) ? $regex->matches[4] : null;
        }

        $this->extractAttributes($markup);

        parent::__construct($markup, $tokens, $fileSystem);
    }

    public function parse(array &$tokens)
    {
        // Skip file loading for block rendering - we'll handle it directly in render()
        if ($this->isBlockRendering) {
            return;
        }
        
        if ($this->fileSystem === null) {
            throw new \Liquid\Exception\MissingFilesystemException("No file system");
        }

        // Adiciona .liquid se não tiver extensão
        if (pathinfo($this->snippetName, PATHINFO_EXTENSION) !== 'liquid') {
            $this->snippetName .= '.liquid';
        }

        try {
            // Obter o conteúdo do snippet
            $source = $this->fileSystem->readTemplateFile($this->snippetName);
            
            // Pré-processamento para remover tags theme-check e normalizar
            $source = $this->preprocessSource($source);
            
            // Verificar o balanceamento das tags
            $this->validateTagBalance($source, $this->snippetName);
            
            $cache = Template::getCache();
            if (!$cache) {
                $templateTokens = Template::tokenize($source);
                $this->document = new Document($templateTokens, $this->fileSystem);
                return;
            }

            $this->hash = md5($source);
            $this->document = $cache->read($this->hash);

            if ($this->document == false || $this->document->hasIncludes() == true) {
                $templateTokens = Template::tokenize($source);
                $this->document = new Document($templateTokens, $this->fileSystem);
                $cache->write($this->hash, $this->document);
            }
        } catch (\Exception $e) {
            // Log do erro com mais detalhes para diagnóstico
            Log::error("Erro ao processar snippet '" . $this->snippetName . "'", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'source_preview' => substr($source ?? '', 0, 200) . '...',
            ]);
            
            throw $e;
        }
    }

    /**
     * Pré-processa a fonte do template para normalizar a sintaxe
     * 
     * @param string $source
     * @return string
     */
    private function preprocessSource(string $source): string
    {
        // Remover tags de theme-check
        $source = $this->removeThemeCheckTags($source);
        
        // Normalizar a sintaxe do Liquid
        $source = $this->normalizeLiquidSyntax($source);
        
        return $source;
    }

    /**
     * Remove as tags theme-check do conteúdo
     * 
     * @param string $content
     * @return string
     */
    private function removeThemeCheckTags(string $content): string
    {
        $patterns = [
            '/{%-?\s*#\s*theme-check-(enable|disable).*?-?%}/s',  // {% # theme-check-disable %}
            '/{%-?\s*theme-check-(enable|disable).*?-?%}/s',      // {% theme-check-disable %}
            '/{%-?\s*#.*?-?%}/s'                                  // Qualquer {% # ... %}
        ];
        
        foreach ($patterns as $pattern) {
            $content = preg_replace($pattern, '', $content);
        }
        
        return $content;
    }
    
    /**
     * Normaliza a sintaxe das tags Liquid para garantir consistência
     * 
     * @param string $content
     * @return string
     */
    private function normalizeLiquidSyntax(string $content): string
    {
        // Remover hífens e normalizar espaços nas tags Liquid
        $content = preg_replace('/\{%-\s*/', '{%', $content);
        $content = preg_replace('/\s*-\%\}/', '%}', $content);
        $content = preg_replace('/\{\{-\s*/', '{{', $content);
        $content = preg_replace('/\s*-\}\}/', '}}', $content);
        
        // Normalizar tabulações e espaços em excesso dentro das tags
        $content = preg_replace_callback('/\{%\s*(.*?)\s*%\}/', function($matches) {
            return '{% ' . trim($matches[1]) . ' %}';
        }, $content);
        
        // Remover quebras de linha dentro das tags (podem causar problemas de parsing)
        $content = preg_replace_callback('/\{%\s*(.*?)\s*%\}/s', function($matches) {
            return '{% ' . preg_replace('/\s+/', ' ', trim($matches[1])) . ' %}';
        }, $content);
        
        return $content;
    }
    
    /**
     * Valida se as tags de abertura têm tags de fechamento correspondentes
     * 
     * @param string $source
     * @param string $snippetName
     * @throws ParseException
     */
    private function validateTagBalance(string $source, string $snippetName): void
    {
        // Verifica se há tags unless sem endunless
        preg_match_all('/{%\s*unless\s+.*?%}/i', $source, $unlessOpen);
        preg_match_all('/{%\s*endunless\s*%}/i', $source, $unlessClose);
        
        if (count($unlessOpen[0]) > count($unlessClose[0])) {
            $diff = count($unlessOpen[0]) - count($unlessClose[0]);
            Log::warning("Possível desbalanceamento de tags no snippet '{$snippetName}': {$diff} tag(s) 'unless' sem 'endunless' correspondente", [
                'source_preview' => substr($source, 0, 500),
                'unless_tags' => $unlessOpen[0],
                'endunless_tags' => $unlessClose[0]
            ]);
        }
        
        // Verifica se há tags if sem endif
        preg_match_all('/{%\s*if\s+.*?%}/i', $source, $ifOpen);
        preg_match_all('/{%\s*endif\s*%}/i', $source, $ifClose);
        
        if (count($ifOpen[0]) > count($ifClose[0])) {
            $diff = count($ifOpen[0]) - count($ifClose[0]);
            Log::warning("Possível desbalanceamento de tags no snippet '{$snippetName}': {$diff} tag(s) 'if' sem 'endif' correspondente", [
                'source_preview' => substr($source, 0, 500)
            ]);
        }
    }

    /**
     * Extrai os atributos do markup
     * 
     * @param string $markup
     */
    protected function extractAttributes($markup)
    {
        $this->attributes = [];
        $attributeRegex = new Regexp('/(\w+)\s*\:\s*(' . Liquid::get('QUOTED_FRAGMENT') . '+)/');
        
        $attributeMatches = [];
        if (preg_match_all('/(\w+)\s*\:\s*(' . Liquid::get('QUOTED_FRAGMENT') . '+)/', $markup, $attributeMatches)) {
            $count = count($attributeMatches[0]);
            for ($i = 0; $i < $count; $i++) {
                $value = $attributeMatches[2][$i];
                $value = preg_replace('/^[\'\"]|[\'\"]$/', '', $value);
                $this->attributes[$attributeMatches[1][$i]] = $value;
            }
        }
    }

    public function render(Context $context)
    {
        // Special handling for {% render block %} (app blocks)
        if ($this->isBlockRendering) {
            $block = $context->get('block');
            if (!$block) {
                Log::warning("Block object not found in context when using {% render block %}");
                return "<!-- Error: block object not found in context when using {% render block %} -->";
            }
            
            Log::debug("Rendering block directly", [
                'block_type' => isset($block['type']) ? $block['type'] : 'unknown',
                'block_id' => isset($block['id']) ? $block['id'] : 'unknown'
            ]);
            
            // For app blocks, just output the shopify_attributes
            if (isset($block['type']) && $block['type'] === '@app') {
                return '<div ' . ($block['shopify_attributes'] ?? '') . '></div>';
            }
            
            // For other block types, include content from settings if available
            $content = '';
            if (isset($block['settings']) && isset($block['settings']['content'])) {
                $content = $block['settings']['content'];
            }
            
            return '<div ' . ($block['shopify_attributes'] ?? '') . '>' . $content . '</div>';
        }

        // Normal snippet rendering logic
        $result = '';
        $variable = $context->get($this->variable);

        $context->push();

        // Detectar arrays em atributos e convertê-los para JSON
        foreach ($this->attributes as $key => $value) {
            $attributeValue = $context->get($value);
            
            // Se for um array, converte para JSON
            if (is_array($attributeValue)) {
                /*
                Log::debug('Convertendo array no atributo: ' . $key, [
                    'value' => json_encode(array_slice($attributeValue, 0, 3, true))
                ]);
                */
                $attributeValue = json_encode($attributeValue);
                }
            
            $context->set($key, $attributeValue);
        }

        try {
            if ($this->collection) {
                foreach ($variable as $item) {
                    // Se o item for um array, converte para um objeto com acesso via propriedade
                    if (is_array($item)) {
                        $item = (object)$item;
                    }
                    
                    $context->set($this->snippetName, $item);
                    $result .= $this->document->render($context);
                }
            } else {
                if (!is_null($this->variable)) {
                    // Se a variável for um array, converte para um objeto
                    if (is_array($variable)) {
                        Log::debug('Convertendo array na variável principal para objeto', [
                            'variable' => $this->variable,
                            'value' => json_encode(array_slice($variable, 0, 3, true))
                        ]);
                        $variable = (object)$variable;
                    }
                    
                    $context->set($this->snippetName, $variable);
                }
                $result .= $this->document->render($context);
            }
        } catch (\Exception $e) {
            Log::error("Erro ao renderizar snippet: " . $this->snippetName, [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            // Re-throw a exceção para que ela possa ser tratada pelo controller
            throw $e;
        } finally {
            $context->pop();
        }

        return $result;
    }
}