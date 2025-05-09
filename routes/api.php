<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\ThemeFileController;



// Rota para criação de lojas (protegida)
Route::post('/create/stores', [StoreController::class, 'store'])
->name('api.stores.create');

Route::post('/create/stores/integration', [StoreController::class, 'integrations'])
->name('api.stores.create.integrations');

Route::get('/themes/{theme}/files', [ThemeFileController::class, 'show']);
Route::post('/themes/{theme}/files', [ThemeFileController::class, 'update']);