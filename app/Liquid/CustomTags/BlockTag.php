<?php
namespace App\Liquid\CustomTags;

use Liquid\Tag\TagBlock;
use Liquid\Context;
use Liquid\Exception\ParseException;

class BlockTag extends TagBlock
{
    // nome da sua tag de abertura
    public static $tag    = 'block';
    // tag de fechamento correspondente
    public static $endtag = 'endblock';

    /** opcional: guardar o nome do bloco para uso interno */
    protected string $name;

    /**
     * Aqui a gente apenas delega ao TagBlock a extração de
     * $this->markup e do conteúdo interno em $this->nodelist,
     * e só depois valida $this->markup para garantir "block nome".
     */
    public function parse(array &$tokens): void
    {
        // primeiro, faz o parse nativo (pega markup e corpo)
        parent::parse($tokens);

        // depois valida que veio exatamente um identificador
        $markup = trim($this->markup);
        if (! preg_match('/^(\w+)$/', $markup, $m)) {
            throw new ParseException(
              "Syntax Error in 'block' - Valid syntax: block [name]"
            );
        }

        // salva o nome (se quiser usar no render)
        $this->name = $m[1];
    }

    /**
     * Renderiza tudo que foi coletado em $this->nodelist.
     */
    public function render(Context $context): string
    {
        $out = '';
        foreach ($this->nodelist as $node) {
            $out .= $node->render($context);
        }
        return $out;
    }
}
