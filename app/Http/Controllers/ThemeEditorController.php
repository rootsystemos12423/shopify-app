<?php

namespace App\Http\Controllers;

use App\Models\Theme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ThemeEditorController extends Controller
{
    public function show($themeId)
    {
        $theme = Theme::where('shopify_theme_id', $themeId)->firstOrFail();
        
        $files = $this->buildFileTree($theme);
        
        Log::debug('Estrutura de arquivos construída', [
            'theme' => $theme->shopify_theme_id,
            'files_count' => count($files)
        ]);
        
        return view('themes.editor', compact('theme', 'files'));
    }

    public function update($themeId, Request $request)
    {
        $validated = $request->validate([
            'path' => 'required|string',
            'content' => 'required|string'
        ]);
        
        $theme = Theme::where('shopify_theme_id', $themeId)->firstOrFail();
        $relativePath = "{$theme->store_id}/{$theme->shopify_theme_id}/{$validated['path']}";
        
        try {
            Storage::disk('themes')->put($relativePath, $validated['content']);
            
            return response()->json([
                'success' => true,
                'message' => 'Arquivo atualizado com sucesso',
                'path' => $relativePath
            ]);
            
        } catch (\Exception $e) {
            Log::error("Erro ao salvar arquivo", [
                'theme' => $themeId,
                'path' => $validated['path'],
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Erro ao salvar arquivo',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    protected function buildFileTree(Theme $theme)
    {
        $rootPath = "{$theme->store_id}/{$theme->shopify_theme_id}";
        $fullPath = Storage::disk('themes')->path($rootPath);
        
        Log::debug('Verificando diretório do tema', [
            'relative_path' => $rootPath,
            'absolute_path' => $fullPath,
            'exists' => Storage::disk('themes')->exists($rootPath)
        ]);
        
        if (!Storage::disk('themes')->exists($rootPath)) {
            Log::error("Diretório do tema não encontrado", ['path' => $rootPath]);
            return [];
        }
        
        $allFiles = Storage::disk('themes')->allFiles($rootPath);
        $tree = [];
        
        foreach ($allFiles as $file) {
            $relativePath = str_replace($rootPath.'/', '', $file);
            $parts = explode('/', $relativePath);
            $this->buildTree($tree, $parts, $file);
        }
        
        return $tree;
    }
    
    protected function buildTree(&$tree, $parts, $fullPath)
    {
        $name = array_shift($parts);
        
        if (empty($parts)) {
            $tree[] = [
                'path' => $fullPath,
                'name' => $name,
                'type' => 'file'
            ];
            return;
        }
        
        foreach ($tree as &$node) {
            if (isset($node['name']) && $node['name'] === $name) {
                $node['children'] = $node['children'] ?? [];
                $this->buildTree($node['children'], $parts, $fullPath);
                return;
            }
        }
        
        $newNode = [
            'path' => dirname($fullPath).'/'.$name, // Caminho completo da pasta
            'name' => $name,
            'type' => 'directory',
            'children' => []
        ];
        $this->buildTree($newNode['children'], $parts, $fullPath);
        $tree[] = $newNode;
    }
}