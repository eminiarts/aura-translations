<?php

use Aura\Translations\Http\Controllers\AiTranslationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix(config('aura.path', 'admin'))
    ->group(function () {
        Route::post('/api/translations/ai', AiTranslationController::class)
            ->name('aura.translations.ai.translate');
    });
