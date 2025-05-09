<?php

namespace App\Services;

use Liquid\Context;

interface ContextProvider
{
    /**
     * Fornece os dados de contexto para o Liquid
     *
     * @param Context $context O contexto Liquid a ser preenchido
     * @param array $params Parâmetros adicionais como tema, requisição, etc.
     * @return void
     */
    public function provide(Context $context, array $params): void;
}