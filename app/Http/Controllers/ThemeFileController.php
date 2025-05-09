<?php

namespace App\Http\Controllers;

use App\Models\Theme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ThemeFileController extends Controller
{
    public function show($themeId, Request $request)
    {
        $request->validate(['path' => 'required|string']);
        
        $theme = Theme::where('shopify_theme_id', $themeId)->first();
        
        if (!$theme) {
            Log::error("Tema não encontrado", ['theme_id' => $themeId]);
            return response()->json(['error' => 'Theme not found'], 404);
        }
        
        // Remove a parte inicial do path se já contiver store_id/version
        $requestPath = $request->path;
        $prefixToRemove = "{$theme->store_id}/{$theme->shopify_theme_id}/";
        
        if (str_starts_with($requestPath, $prefixToRemove)) {
            $requestPath = substr($requestPath, strlen($prefixToRemove));
        }
        
        $fullPath = "{$theme->store_id}/{$theme->shopify_theme_id}/{$requestPath}";
        
        Log::debug("Tentando acessar arquivo", [
            'request_path' => $request->path,
            'full_path' => $fullPath,
            'exists' => Storage::disk('themes')->exists($fullPath)
        ]);
        
        if (!Storage::disk('themes')->exists($fullPath)) {
            Log::error("Arquivo não encontrado", ['path' => $fullPath]);
            return response()->json(['error' => 'File not found'], 404);
        }
        
        return response()->json([
            'content' => Storage::disk('themes')->get($fullPath),
            'path' => $requestPath
        ]);
    }

    public function update($themeId, Request $request)
    {
        $request->validate([
            'path' => 'required|string',
            'content' => 'required|string'
        ]);
        
        $theme = Theme::where('shopify_theme_id', $themeId)->first();
        
        if (!$theme) {
            return response()->json(['error' => 'Theme not found'], 404);
        }
        
        // Remove a parte inicial do path se já contiver store_id/version
        $requestPath = $request->path;
        $prefixToRemove = "{$theme->store_id}/{$theme->shopify_theme_id}/";
        
        if (str_starts_with($requestPath, $prefixToRemove)) {
            $requestPath = substr($requestPath, strlen($prefixToRemove));
        }
        
        $fullPath = "{$theme->store_id}/{$theme->shopify_theme_id}/{$requestPath}";
        
        // Garante que o diretório existe
        Storage::disk('themes')->makeDirectory(dirname($fullPath));
        
        Storage::disk('themes')->put($fullPath, $request->content);
        
        return response()->json([
            'success' => true,
            'message' => 'File updated successfully',
            'path' => $requestPath
        ]);
    }
}