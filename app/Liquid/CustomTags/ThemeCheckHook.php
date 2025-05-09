<?php

namespace App\Liquid\CustomTags;

use Liquid\AbstractTag;
use Liquid\Context;
use Liquid\Document;
use Liquid\Liquid;
use Liquid\LiquidException;
use Liquid\FileSystem;

class ThemeCheckHook
{
    /**
     * Registra o hook para processar corretamente as tags theme-check
     */
    public static function register()
    {
        // Ajusta a expressão regular para aceitar também as tags com '#'
        $originalRegex = Liquid::get('TOKENIZATION_REGEXP');
        
        // Modifica a expressão regular para aceitar '#' como parte do nome da tag
        $newRegex = str_replace(
            '(\w+)', // Nome da tag original (apenas alfanumérico)
            '(#?\s*\w+(?:-\w+)*)', // Nome da tag modificado para aceitar '#', espaços e hifens
            $originalRegex
        );
        
        // Registra a nova expressão regular
        Liquid::set('TOKENIZATION_REGEXP', $newRegex);
    }
}