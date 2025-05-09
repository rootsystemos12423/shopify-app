<?php
namespace App\Liquid;

use Keepsuit\Liquid\Contracts\LiquidFileSystem;
use Illuminate\Support\Facades\Storage;

class ThemeFileSystem implements LiquidFileSystem
{
    public function __construct(
        protected int    $storeId,
        protected string $themeVersion,
    ) {}

    /**
     * Carrega snippets/includes sob {storeId}/{themeVersion}/snippets/
     */
    public function readTemplateFile(string $templateName): string
    {
        $candidates = [
            "{$this->storeId}/{$this->themeVersion}/snippets/{$templateName}.liquid",
            "{$this->storeId}/{$this->themeVersion}/snippets/{$templateName}.json",
        ];

        foreach ($candidates as $path) {
            if (Storage::disk('themes')->exists($path)) {
                return Storage::disk('themes')->get($path);
            }
        }

        throw new \RuntimeException("Snippet não encontrado: {$templateName}");
    }
}
