<?php

namespace App\Liquid\CustomTags;

use Liquid\AbstractBlock;
use Liquid\Liquid;
use Liquid\Context;
use Liquid\LiquidException;
use Liquid\FileSystem;
use Liquid\Regexp;

/**
 * CustomTagFor com compatibilidade total com a Shopify
 * Implementa todos os recursos da tag for do Shopify:
 * - Iteração sobre coleções
 * - Intervalos numéricos (1..10)
 * - Parâmetros limit, offset e reversed
 * - Blocos else
 * - Variáveis forloop completas (index, index0, first, last, etc.)
 * - Compatibilidade com tags break e continue
 * - Suporte a loops aninhados com forloop.parent
 *
 * Exemplo:
 *     {% for item in collection %} {{item}} {% endfor %}
 *     {% for i in (1..10) %} {{i}} {% endfor %}
 */
class TagFor extends AbstractBlock
{
    /**
     * @var array Nome da coleção a ser iterada
     */
    private $collectionName;

    /**
     * @var string Nome da variável para atribuir elementos da coleção
     */
    private $variableName;

    /**
     * @var string Nome do loop (composto pelo nome da variável e da coleção)
     */
    private $name;
    
    /**
     * @var string Tipo do loop (collection ou digit)
     */
    private $type = 'collection';

    /**
     * Array mantendo os nós para renderizar para cada bloco lógico
     *
     * @var array
     */
    private $nodelistHolders = array();

    /**
     * Array mantendo o tipo do bloco, markup e nodelist
     *
     * @var array
     */
    protected $blocks = array();

    /**
     * Construtor
     *
     * @param string $markup
     * @param array $tokens
     * @param FileSystem $fileSystem
     *
     * @throws \Liquid\LiquidException
     */
    public function __construct($markup, array &$tokens, FileSystem $fileSystem = null) {
        $this->nodelist = & $this->nodelistHolders[count($this->blocks)];
        array_push($this->blocks, array('for', $markup, &$this->nodelist));

        parent::__construct($markup, $tokens, $fileSystem);

        $syntaxRegexp = new Regexp('/(\w+)\s+in\s+(' . Liquid::get('VARIABLE_NAME') . ')/');

        if ($syntaxRegexp->match($markup)) {
            $this->variableName = $syntaxRegexp->matches[1];
            $this->collectionName = $syntaxRegexp->matches[2];
            $this->name = $syntaxRegexp->matches[1] . '-' . $syntaxRegexp->matches[2];
            $this->extractAttributes($markup);
        } else {
            $syntaxRegexp = new Regexp('/(\w+)\s+in\s+\((\d+|' . Liquid::get('VARIABLE_NAME') . ')\s*\.\.\s*(\d+|' . Liquid::get('VARIABLE_NAME') . ')\)/');
            if ($syntaxRegexp->match($markup)) {
                $this->type = 'digit';
                $this->variableName = $syntaxRegexp->matches[1];
                $this->start = $syntaxRegexp->matches[2];
                $this->collectionName = $syntaxRegexp->matches[3];
                $this->name = $syntaxRegexp->matches[1].'-digit';
                $this->extractAttributes($markup);
            } else {
                throw new LiquidException("Syntax Error in 'for loop' - Valid syntax: for [item] in [collection]");
            }
        }
    }

    /**
     * Manipulador para tags desconhecidas, lidar com tags else
     *
     * @param string $tag
     * @param array $params
     * @param array $tokens
     */
    public function unknownTag($tag, $params, array $tokens) {
        if ($tag == 'else') {
            // Atualizar referência para nodelistHolder para este bloco
            $this->nodelist = & $this->nodelistHolders[count($this->blocks) + 1];
            $this->nodelistHolders[count($this->blocks) + 1] = array();

            array_push($this->blocks, array($tag, $params, &$this->nodelist));
        } else {
            parent::unknownTag($tag, $params, $tokens);
        }
    }

    /**
     * Renderiza a tag
     *
     * @param Context $context
     *
     * @return null|string
     */
    public function render(Context $context) {
        if (!isset($context->registers['for'])) {
            $context->registers['for'] = array();
        }

        // Salvar loop pai se disponível (para compatibilidade com Shopify)
        $parentForloop = null;
        if ($context->hasKey('forloop')) {
            $parentForloop = $context->get('forloop');
        }

        switch ($this->type) {
            case 'collection':
                $collection = $context->get($this->collectionName);

                if ($collection instanceof \Traversable) {
                    $collection = iterator_to_array($collection);
                }
        
                if (is_null($collection) || !is_array($collection) || count($collection) == 0) {
                    $context->push();
                    $nodelist = isset($this->nodelistHolders[1]) ? $this->nodelistHolders[1] : array();
                    $result = $this->renderAll($nodelist, $context);
                    $context->pop();
                    return $result;
                }

                if ($this->attributes['reversed']) {
                    $collection = array_reverse($collection);
                }

                $range = array(0, count($collection));
        
                if (isset($this->attributes['limit']) || isset($this->attributes['offset'])) {
                    $offset = 0;

                    if (isset($this->attributes['offset'])) {
                        $offset = ($this->attributes['offset'] == 'continue') ? $context->registers['for'][$this->name] : $context->get($this->attributes['offset']);
                    }
        
                    $limit = (isset($this->attributes['limit'])) ? $context->get($this->attributes['limit']) : null;
                    $rangeEnd = $limit ? $limit : count($collection) - $offset;
                    $range = array($offset, $rangeEnd);
        
                    $context->registers['for'][$this->name] = $rangeEnd + $offset;
                }
        
                $result = '';
                $segment = array_slice($collection, $range[0], $range[1]);

                if (!count($segment)) {
                    // Se o segmento estiver vazio, renderizar o bloco else se existir
                    $context->push();
                    $nodelist = isset($this->nodelistHolders[1]) ? $this->nodelistHolders[1] : array();
                    $result = $this->renderAll($nodelist, $context);
                    $context->pop();
                    return $result;
                }

                $context->push();
                $length = count($segment);
                $index = 0;
                $nodelist = $this->nodelistHolders[0];

                foreach ($segment as $key => $item) {
                    $value = is_numeric($key) ? $item : array($key, $item);
                    $context->set($this->variableName, $value);
                    $context->set('forloop', array(
                        'name' => $this->name,
                        'length' => $length,
                        'index' => $index + 1,
                        'index0' => $index,
                        'rindex' => $length - $index,
                        'rindex0' => $length - $index - 1,
                        'first' => $index == 0,
                        'last' => $index == $length - 1,
                        'parent' => $parentForloop // Adicionado para compatibilidade com Shopify
                    ));

                    $result .= $this->renderAll($nodelist, $context);
                    
                    $index++;

                    if (isset($context->registers['break'])) {
                        unset($context->registers['break']);
                        break;
                    }
                    if (isset($context->registers['continue'])) {
                        unset($context->registers['continue']);
                        continue; // Use continue em vez de ignorar para compatibilidade real com Shopify
                    }
                }
                
                break;
            
            case 'digit':
                $start = $this->start;
                if (!is_integer($this->start) && !is_numeric($this->start)) {
                    $start = $context->get($this->start);
                } else {
                    $start = (int)$start;
                }

                $end = $this->collectionName;
                if (!is_integer($this->collectionName) && !is_numeric($this->collectionName)) {
                    $end = $context->get($this->collectionName);
                } else {
                    $end = (int)$end;
                }

                $context->push();
                $result = '';
                $index = 0;
                $length = $end - $start + 1;
                $nodelist = $this->nodelistHolders[0];

                // Lidar com casos em que o intervalo é inválido
                if ($length <= 0) {
                    // Renderizar o bloco else se existir
                    $nodelist = isset($this->nodelistHolders[1]) ? $this->nodelistHolders[1] : array();
                    $result = $this->renderAll($nodelist, $context);
                    $context->pop();
                    return $result;
                }

                $limit = isset($this->attributes['limit']) ? (int) $context->get($this->attributes['limit']) : -1;
                $offset = isset($this->attributes['offset']) ? (int) $context->get($this->attributes['offset']) : 0;

                if ($this->attributes['reversed']) {
                    for ($i=$end; $i>=$start; $i--) {
                        if ($offset > $end - $i) {
                            continue;
                        }

                        $context->set($this->variableName, $i);
                        $context->set('forloop', array(
                            'name'      => $this->name,
                            'length'    => $length,
                            'index'     => $index + 1,
                            'index0'    => $index,
                            'rindex'    => $length - $index,
                            'rindex0'   => $length - $index - 1,
                            'first'     => $index == 0,
                            'last'      => $index == $length - 1,
                            'parent'    => $parentForloop
                        ));

                        $result .= $this->renderAll($nodelist, $context);
                        
                        $index++;

                        if ($limit != -1 && $index == $limit) {
                            break;
                        }

                        if (isset($context->registers['break'])) {
                            unset($context->registers['break']);
                            break;
                        }
                        if (isset($context->registers['continue'])) {
                            unset($context->registers['continue']);
                            continue;
                        }
                    }
                } else {
                    for ($i=$start; $i<=$end; $i++) {
                        if ($offset > $i - $start) {
                            continue;
                        }

                        $context->set($this->variableName, $i);
                        $context->set('forloop', array(
                            'name'      => $this->name,
                            'length'    => $length,
                            'index'     => $index + 1,
                            'index0'    => $index,
                            'rindex'    => $length - $index,
                            'rindex0'   => $length - $index - 1,
                            'first'     => $index == 0,
                            'last'      => $index == $length - 1,
                            'parent'    => $parentForloop
                        ));

                        $result .= $this->renderAll($nodelist, $context);

                        $index++;

                        if ($limit != -1 && $index == $limit) {
                            break;
                        }

                        if (isset($context->registers['break'])) {
                            unset($context->registers['break']);
                            break;
                        }
                        if (isset($context->registers['continue'])) {
                            unset($context->registers['continue']);
                            continue;
                        }
                    }
                }
                break;
        }

        $context->pop();
        return $result;
    }

    /**
     * Extrai atributos de uma string de markup.
     *
     * @param string $markup
     */
    protected function extractAttributes($markup) {
        parent::extractAttributes($markup);
        
        // Verificar flag reversed
        $reversedRegexp = new Regexp('/reversed/');
        $this->attributes['reversed'] = !!$reversedRegexp->match($markup);
    }
}