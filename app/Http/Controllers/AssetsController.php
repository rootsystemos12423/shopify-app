<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Support\Facades\Response;



class AssetsController extends Controller
{
    /**
     * Serve arquivos de asset
     */
    public function serveAsset(string $path): StreamedResponse
    {
        // Extrai store_id e theme_id do caminho
        $pathParts = explode('/', $path, 3);
        
        if (count($pathParts) < 3) {
            throw new NotFoundHttpException('Asset não encontrado - caminho inválido');
        }
    
        [$storeId, $themeId, $assetPath] = $pathParts;
    
        // Caminho completo do asset no storage
        $fullPath = "{$storeId}/{$themeId}/assets/{$assetPath}";
    
        // Verifica se o arquivo existe
        if (!Storage::disk('themes')->exists($fullPath)) {
            throw new NotFoundHttpException("Asset não encontrado: {$fullPath}");
        }
    
        // Obtém o stream do arquivo
        $stream = Storage::disk('themes')->readStream($fullPath);
        
        if (!$stream) {
            throw new NotFoundHttpException("Não foi possível ler o arquivo: {$fullPath}");
        }
    
        // Determina o tipo MIME do arquivo
        $mimeType = Storage::disk('themes')->mimeType($fullPath);
    
        // Retorna o arquivo como resposta
        return Response::stream(function() use ($stream) {
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=31536000', // Cache de 1 ano
        ]);
    }

    /**
     * Serve font files
     */
    public function serveFont(string $fontFamily, string $fontFile): StreamedResponse
    {
        // Normalize font family name to lowercase for directory matching
        $fontFamily = strtolower($fontFamily);
        
        // Full path to the font file
        $fullPath = "fonts/{$fontFamily}/{$fontFile}";
        
        // Verify the file exists in the public disk
        if (!Storage::disk('public')->exists($fullPath)) {
            throw new NotFoundHttpException("Font file not found: {$fullPath}");
        }
        
        // Get the file stream
        $stream = Storage::disk('public')->readStream($fullPath);
        
        if (!$stream) {
            throw new NotFoundHttpException("Could not read font file: {$fullPath}");
        }
        
        // Determine MIME type based on file extension
        $extension = pathinfo($fontFile, PATHINFO_EXTENSION);
        $mimeType = $this->getFontMimeType($extension);
        
        // Return the file as a streamed response
        return Response::stream(function() use ($stream) {
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=31536000', // 1 year cache
            'Access-Control-Allow-Origin' => '*' // Allow cross-origin requests for fonts
        ]);
    }

    /**
     * Get MIME type for font files
     */
    private function getFontMimeType(string $extension): string
    {
        return match (strtolower($extension)) {
            'woff2' => 'font/woff2',
            'woff' => 'font/woff',
            'ttf' => 'font/ttf',
            'otf' => 'font/otf',
            'eot' => 'application/vnd.ms-fontobject',
            'svg' => 'image/svg+xml',
            default => 'application/octet-stream',
        };
    }

    /**
     * Serves theme JavaScript files with caching
     */
    public function serveScript(Request $request, $scriptName)
    {
        // Construct the correct path for the script
        $path = "scripts/{$scriptName}";
        $fullPath = public_path("storage/{$path}");
        
        // Check if file exists using file_exists since we're using direct paths now
        if (!file_exists($fullPath)) {
            throw new NotFoundHttpException("Script not found: {$scriptName}");
        }
        
        // Get file modification time for cache checks
        $lastModified = filemtime($fullPath);
        $etag = md5($lastModified . $fullPath);
        
        // Check if client has a cached version
        if ($request->header('If-None-Match') == $etag) {
            return response()->make('', 304);
        }
        
        // Check if modified since the client's cached version
        $ifModifiedSince = $request->header('If-Modified-Since');
        if ($ifModifiedSince && strtotime($ifModifiedSince) >= $lastModified) {
            return response()->make('', 304);
        }
        
        // Generate a cache key
        $cacheKey = 'script_' . md5($fullPath . $lastModified);
        
        // Try to get from cache
        $content = Cache::remember($cacheKey, now()->addDay(), function () use ($fullPath) {
            // Get the content directly from the file
            $rawContent = file_get_contents($fullPath);
            
            // Process includes/imports
            return $this->processScriptIncludes($rawContent);
        });
        
        // Cache control and headers
        $headers = [
            'Content-Type' => 'application/javascript; charset=utf-8',
            'Cache-Control' => 'public, max-age=31536000',
            'ETag' => $etag,
            'Last-Modified' => gmdate('D, d M Y H:i:s', $lastModified) . ' GMT',
            'Expires' => gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT',
        ];
        
        // Return the content with appropriate headers
        return response($content, 200, $headers);
    }

    /**
     * Process script includes/imports
     */
    private function processScriptIncludes(string $content): string
    {
        // Process // @include "filename.js" directives
        return preg_replace_callback(
            '/\/\/\s*@include\s+["\']([^"\']+)["\']/i',
            function($matches) {
                $includeScriptName = $matches[1];
                $includePath = public_path("storage/scripts/{$includeScriptName}");
                
                if (file_exists($includePath)) {
                    // We could create a nested cache here, but for simplicity we'll just read the file
                    // For deep include trees, you might want to implement recursive caching
                    return file_get_contents($includePath);
                }
                
                return "// Include not found: {$includeScriptName}";
            },
            $content
        );
    }

    /**
     * Serves theme CSS files with caching
     */
    public function serveStyles(Request $request, $styleName)
    {
        // Construct the correct path for the style
        $path = "styles/{$styleName}";
        $fullPath = public_path("storage/{$path}");
        
        // Check if file exists using file_exists since we're using direct paths now
        if (!file_exists($fullPath)) {
            throw new NotFoundHttpException("Style not found: {$styleName}");
        }
        
        // Get file modification time for cache checks
        $lastModified = filemtime($fullPath);
        $etag = md5($lastModified . $fullPath);
        
        // Check if client has a cached version
        if ($request->header('If-None-Match') == $etag) {
            return response()->make('', 304);
        }
        
        // Check if modified since the client's cached version
        $ifModifiedSince = $request->header('If-Modified-Since');
        if ($ifModifiedSince && strtotime($ifModifiedSince) >= $lastModified) {
            return response()->make('', 304);
        }
        
        // Generate a cache key
        $cacheKey = 'style_' . md5($fullPath . $lastModified);
        
        // Try to get from cache
        $content = Cache::remember($cacheKey, now()->addDay(), function () use ($fullPath) {
            // Get the content directly from the file
            $rawContent = file_get_contents($fullPath);
            
            // Process includes/imports
            return $this->processStylesIncludes($rawContent);
        });
        
        // Cache control and headers - FIXED: correct Content-Type for CSS
        $headers = [
            'Content-Type' => 'text/css; charset=utf-8',
            'Cache-Control' => 'public, max-age=31536000',
            'ETag' => $etag,
            'Last-Modified' => gmdate('D, d M Y H:i:s', $lastModified) . ' GMT',
            'Expires' => gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT',
        ];
        
        // Return the content with appropriate headers
        return response($content, 200, $headers);
    }

    /**
     * Process CSS includes/imports
     */
    private function processStylesIncludes(string $content): string
    {
        // Process @import directives for CSS
        return preg_replace_callback(
            '/@import\s+[\'"]([^\'"]+)[\'"]\s*;/i',
            function($matches) {
                $includeStylesName = $matches[1];
                $includePath = public_path("storage/styles/{$includeStylesName}");
                
                if (file_exists($includePath)) {
                    return file_get_contents($includePath);
                }
                
                return "/* Import not found: {$includeStylesName} */";
            },
            $content
        );
    }

}
