<?php

namespace App\Liquid\CustomTags;

use Liquid\AbstractBlock;
use Liquid\Context;
use Liquid\FileSystem;
use Liquid\LiquidException;
use Liquid\Regexp;
use Liquid\Template;

class SectionTag extends AbstractBlock
{
    /**
     * @var string Nome da seção
     */
    private $sectionName;

    /**
     * @var FileSystem Sistema de arquivos
     */
    protected $fileSystem;

    protected $blocks = array();

    /**
     * Constructor
     */
    public function __construct($markup, array &$tokens, FileSystem $fileSystem = null)
    {
        // Configuração inicial igual à TagFor
        $this->nodelist = &$this->nodelistHolders[count($this->blocks)];
        array_push($this->blocks, ['section', $markup, &$this->nodelist]);
        
        parent::__construct($markup, $tokens, $fileSystem);
        $this->fileSystem = $fileSystem;

        // Extrai o nome da seção usando regex
        $syntaxRegexp = new Regexp('/["\']?([\w-]+)["\']?/');
        if ($syntaxRegexp->match($markup)) {
            $this->sectionName = $syntaxRegexp->matches[1];
        } else {
            throw new LiquidException("Syntax Error in 'section' - Valid syntax: {% section 'name' %}");
        }
    }

    /**
     * Renderiza a seção
     */
    public function render(Context $context): string
    {
        try {
            // Carrega o conteúdo da seção
            $path = "sections/{$this->sectionName}.liquid";
            $content = $this->fileSystem->readTemplateFile($path);

            // Cria um sub-template para a seção
            $template = new Template();
            $template->setFileSystem($this->fileSystem);
            $template->parse($content);

            // Cria um novo escopo para evitar vazamento de variáveis
            $context->push();
            $result = $template->render($context->all());
            $context->pop();

            return $result;

        } catch (LiquidException $e) {
            $context->pop();
            return "<!-- Section error: {$e->getMessage()} -->";
        }
    }

    /**
     * Mantém compatibilidade com estrutura de blocos
     */
    protected function parseBlock(array &$tokens): void
    {
        // Seções não possuem blocos internos
        $this->nodelist = $this->nodelistHolders[0];
        $this->nodelistHolders[0] = [];
    }

    /**
     * Extrai atributos adicionais (se necessário)
     */
    protected function extractAttributes($markup): void
    {
        // Implementação similar à TagFor
        parent::extractAttributes($markup);
        // Pode extrair atributos como: {% section 'nome' key:value %}
    }
}