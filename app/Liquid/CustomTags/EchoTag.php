<?php

namespace App\Liquid\CustomTags;

use Liquid\AbstractTag;
use Liquid\Context;
use Liquid\FileSystem;
use Liquid\LiquidException;

/**
 * EchoTag - Implementação da tag {% echo %} para uso dentro de {% liquid %}
 */
class EchoTag extends AbstractTag
{
    /**
     * @var string Conteúdo a ser exibido
     */
    private $markup;

    /**
     * Construtor
     *
     * @param string $markup O conteúdo a ser exibido
     * @param array $tokens Tokens do parser
     * @param FileSystem $fileSystem Sistema de arquivos
     */
    public function __construct($markup, array &$tokens, FileSystem $fileSystem = null)
    {
        parent::__construct($markup, $tokens, $fileSystem);
        $this->markup = $markup;
    }

    /**
     * Renderiza a tag
     *
     * @param Context $context O contexto de execução Liquid atual
     * @return string O resultado renderizado
     */
    public function render(Context $context)
    {
        try {
            // Se o conteúdo já estiver entre aspas, retorna diretamente o conteúdo
            if (preg_match('/^[\'"](.*)[\'"]$/s', $this->markup, $matches)) {
                return $matches[1];
            }
            
            // Processa o conteúdo usando o sistema de variáveis do Liquid
            return $context->get($this->markup);
        } catch (\Exception $e) {
            throw new LiquidException('Error in echo tag: ' . $e->getMessage(), 0, $e);
        }
    }
}