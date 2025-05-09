<?php

namespace App\Services;

use Liquid\Template;

class LiquidRenderer
{
    public function render($templateString, $data = [])
    {
        $template = new Template();
        $template->parse($templateString);
        return $template->render($data);
    }
}
