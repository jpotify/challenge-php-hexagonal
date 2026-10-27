<?php

use App\Infrastructure\Http\Auth\LoginController;
use App\Infrastructure\Http\Gif\GifController;
use App\Infrastructure\Http\Middleware\AuditApiRequest;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(AuditApiRequest::class)->group(function (): void {
    Route::post('auth/login', LoginController::class)
        ->middleware('throttle:10,1')
        ->name('auth.login');

    Route::middleware('auth:api')->group(function (): void {
        Route::get('gifs', [GifController::class, 'index'])->name('gifs.search');
        Route::get('gifs/{id}', [GifController::class, 'show'])
            ->where('id', '[A-Za-z0-9]{1,64}')
            ->name('gifs.show');
        Route::post('favorites', [GifController::class, 'storeFavorite'])->name('favorites.store');
    });
});
