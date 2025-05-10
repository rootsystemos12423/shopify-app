<?php

namespace App\Liquid\CustomTags;

use Liquid\Tag\AbstractTag;
use Liquid\Context;
use Liquid\Template;
use Liquid\FileSystem\Local;

/**
 * Custom Liquid tag to execute inline Liquid markup (assign, capture, etc.)
 * Usage:
 *   {%- liquid
 *     assign foo = 'bar'
 *     assign baz = foo | upcase
 *   -%}
 * After rendering, variables `foo` and `baz` will be available in the context.
 */
class LiquidTag extends AbstractTag
{
    /**
     * Nome da tag: {% liquid ... %}
     */
    public static function tagName(): string
    {
        return 'liquid';
    }

    /**
     * Motor Liquid reutilizado para processar o markup interno
     * @var Template
     */
    protected Template $engine;

    public function __construct($markup, array &$tokens, Local $fileSystem = null)
    {
        parent::__construct($markup, $tokens, $fileSystem);
        // Inicializa um engine separado, mantendo mesmos filtros e tags já registrados
        $this->engine = new Template();
        // Herdar FileSystem e filtros do engine principal, se necessário
        $this->engine->setFileSystem($fileSystem ?: new Local(''));
    }

    /**
     * Renderiza o markup interno como um mini-template Liquid
     */
    public function render(Context $context): string
    {
        // Envolve o markup em delimitadores para parsear as instruções
        $liquidCode = sprintf('{%% %s %%}', trim($this->markup));

        // Parseia e renderiza com o contexto atual
        $this->engine->parse($liquidCode);
        return $this->engine->render($context);
    }
}
