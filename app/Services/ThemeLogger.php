<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class ThemeLogger
{
    // Níveis de log
    const ERROR = 'error';
    const WARNING = 'warning';
    const INFO = 'info';

    /**
     * Registra um erro específico de Array to String conversion
     */
    public static function arrayConversionError($message, $context = [])
    {
        Log::error("[THEME_ERROR] Array to string conversion: {$message}", $context);
    }

    /**
     * Registra outros erros de tema
     */
    public static function error($message, $context = [])
    {
        Log::error("[THEME_ERROR] {$message}", $context);
    }

    /**
     * Registra um aviso
     */
    public static function warning($message, $context = [])
    {
        Log::warning("[THEME_WARNING] {$message}", $context);
    }

    /**
     * Registra informações sobre o processo
     */
    public static function info($message, $context = [])
    {
        Log::info("[THEME_INFO] {$message}", $context);
    }
}