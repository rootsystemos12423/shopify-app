<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Liquid\Template;
use Liquid\Exception\ParseException;
use App\Services\ThemeLogger;

class ThemeErrorHandler
{
    /**
     * Maximum content size for templates
     */
    const MAX_CONTENT_SIZE = 2000000; // 2MB

    /**
     * Handle and log rendering errors
     */
    public function handleRenderingError(\Throwable $e, string $templateName = 'unknown'): string
    {
        // If it's an Array to String conversion error
        if (strpos($e->getMessage(), 'Array to string conversion') !== false) {
            // Get stack trace information
            $trace = $e->getTrace();
            $errorFile = $e->getFile();
            $errorLine = $e->getLine();
            
            // Identify where the error occurred
            $contextInfo = [];
            foreach ($trace as $frame) {
                if (isset($frame['file']) && strpos($frame['file'], 'Liquid') !== false) {
                    $contextInfo['liquid_file'] = $frame['file'];
                    $contextInfo['liquid_line'] = $frame['line'];
                    break;
                }
            }
            
            // Log detailed error
            ThemeLogger::arrayConversionError("Error in template {$templateName}", [
                'file' => $errorFile,
                'line' => $errorLine,
                'liquid_context' => $contextInfo
            ]);
            
            // In development, return informative message
            if (config('app.env') === 'local') {
                return "<!-- Array to string conversion error in template {$templateName} -->";
            }
        } else {
            // Other types of errors
            ThemeLogger::error($e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'template' => $templateName
            ]);
            
            // In development
            if (config('app.env') === 'local') {
                return "<!-- Error rendering template {$templateName}: {$e->getMessage()} -->";
            }
        }
        
        // In production, return empty string
        return '';
    }

    /**
     * Handle general rendering errors
     */
    public function handleRenderError(\Throwable $e, Request $request): \Illuminate\Http\JsonResponse
    {
        Log::error('Theme rendering failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'request' => $request->all(),
        ]);

        return response()->json([
            'error' => 'Renderization Error',
            'details' => $e->getMessage(),
            'domain' => $request->getHost(),
            'storage_root' => config('filesystems.disks.themes.root'),
        ], 500);
    }

    /**
     * Log parsing errors
     */
    public function logParsingError(ParseException $e, string $content): void
    {
        ThemeLogger::error('Liquid Parsing Error', [
            'message' => $e->getMessage(),
            'snippet' => Str::limit($content, 500),
            'trace' => $e->getTraceAsString()
        ]);
    }

    /**
     * Track array conversion errors
     */
    public function trackArrayConversionError(array $context): void
    {
        $arraysFound = [];
        
        // Look for arrays in the context
        foreach ($context as $key => $value) {
            if (is_array($value)) {
                $arraysFound[$key] = [
                    'type' => 'array',
                    'count' => count($value),
                    'sample' => array_slice($value, 0, 3, true)
                ];
            }
        }
        
        ThemeLogger::info("Arrays found in context", [
            'arrays' => $arraysFound
        ]);
    }

    /**
     * Capture details of template with error
     */
    public function captureTemplateError(string $templatePath, string $errorMsg, string $content = ''): void
    {
        ThemeLogger::error('template_processing', "Error processing template: {$errorMsg}", [
            'template' => $templatePath,
            'content_preview' => substr($content, 0, 200)
        ]);
        
        // Save the problematic template for later analysis
        $errorDir = storage_path('logs/template_errors');
        if (!is_dir($errorDir)) {
            mkdir($errorDir, 0755, true);
        }
        
        $filename = $errorDir . '/' . basename($templatePath) . '_' . date('Y-m-d_H-i-s') . '.liquid';
        file_put_contents($filename, $content);
        
        ThemeLogger::info('template_processing', "Problematic template saved at: {$filename}");
    }

    /**
     * Render with error handling and logging
     */
    public function safeRender(Template $template, array $context, string $templateName = 'unknown'): string
    {
        try {
            return $template->render($context);
        } catch (\Throwable $e) {
            // Log detailed error
            ThemeLogger::error('template_render', "Error rendering template: {$templateName}", [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            // For array to string conversion errors
            if (strpos($e->getMessage(), 'Array to string conversion') !== false) {
                $this->trackArrayConversionError($context);
            }
            
            // In development mode
            if (config('app.env') === 'local') {
                return "<!-- Rendering error: {$e->getMessage()} -->";
            }
            
            // In production
            return '';
        }
    }

    /**
     * Verify template content size
     */
    public function checkContentSize(string $content): string
    {
        if (strlen($content) > self::MAX_CONTENT_SIZE) {
            return substr($content, 0, self::MAX_CONTENT_SIZE) . 
                "\n<!-- Content truncated due to size limitations -->";
        }
        return $content;
    }
}