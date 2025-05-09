<?php

namespace App\Exceptions;

use App\Services\ThemeLogger;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    // Código existente...
    
    /**
     * Registra uma exceção.
     *
     * @param  \Throwable  $e
     * @return void
     */
    public function report(Throwable $e)
    {
        // Detectar erros de Array to String
        if ($e instanceof \ErrorException && 
            strpos($e->getMessage(), 'Array to string conversion') !== false) {
            
            // Registrar com detalhes para facilitar depuração
            ThemeLogger::arrayToStringError(
                $e->getFile(),
                $e->getLine(),
                $this->getArrayFromStackTrace($e)
            );
        }
        
        parent::report($e);
    }
    
    /**
     * Tenta extrair o array problemático da stack trace
     */
    private function getArrayFromStackTrace(Throwable $e): array
    {
        $trace = $e->getTrace();
        
        // Normalmente, o array está em algum lugar nos argumentos da função
        foreach ($trace as $frame) {
            if (isset($frame['args'])) {
                foreach ($frame['args'] as $arg) {
                    if (is_array($arg)) {
                        return $arg;
                    }
                }
            }
        }
        
        return [];
    }
}