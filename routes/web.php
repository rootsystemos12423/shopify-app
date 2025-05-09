<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ThemeFileController;
use App\Http\Controllers\ThemeEditorController;
use App\Http\Controllers\ThemeController;
use App\Http\Controllers\AssetsController;
use App\Http\Controllers\ProductController;


// Rotas públicas
Route::get('assets/{path}', [AssetsController::class, 'serveAsset'])
     ->where('path', '.*')
     ->name('theme.assets');

    // Route for serving scripts
    Route::get('/scripts/{script}', [AssetsController::class, 'serveScript'])
    ->where('script', '.*\.js')
    ->name('theme.script');

    Route::get('/styles/{style}', [AssetsController::class, 'serveStyles'])
    ->where('style', '.*\.css')
    ->name('theme.css');

Route::get('fonts/{fontFamily}/{fontFile}', [AssetsController::class, 'serveFont'])
     ->name('theme.fonts');     


     Route::get('/', [ThemeController::class, 'renderTemplate']);
     Route::get('/404', [ThemeController::class, 'renderTemplate']);
     Route::get('/cart', [ThemeController::class, 'renderTemplate']);
     
     // Product routes
     Route::get('/products/{product}', [ProductController::class, 'renderProduct']);

     Route::get('/recommendations/products', [ProductController::class, 'recommendation']);
     
     // Fallback route - will be handled by ThemeController
     Route::fallback([ThemeController::class, 'renderTemplate']);
    

Route::get('/theme-preview', [ThemeController::class, 'preview']);

// Rotas autenticadas
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    
    // Dashboard principal
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/stores', [StoreController::class, 'list'])->name('store.selector');
    Route::post('/store/select', [StoreController::class, 'select'])->name('store.select');

    // Grupo para operações de loja
    Route::prefix('/dashboard')->group(function () {
        // Exibir formulário de criação
        Route::get('/create-store', [StoreController::class, 'show'])
            ->name('create.store');
            
            Route::get('/themes/{theme}/editor', [ThemeEditorController::class, 'show'])
            ->name('themes.editor')
            ->where('theme', '[0-9]+');

        // Gerenciar credenciais
        Route::get('/credentials', [StoreController::class, 'credentials'])
            ->name('create.credentials');
            
        
    });
});