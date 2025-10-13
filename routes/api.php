<?php 
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\PreferenceController;
use App\Http\Controllers\SourceController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Articles endpoints
    Route::prefix('articles')->group(function () {
        Route::get('/', [ArticleController::class, 'index']);
        Route::get('/search', [ArticleController::class, 'search']);
        Route::get('/popular', [ArticleController::class, 'popular']);
        Route::get('/category/{category}', [ArticleController::class, 'byCategory']);
        Route::get('/source/{source}', [ArticleController::class, 'bySource']);
        Route::get('/{id}', [ArticleController::class, 'show']);
        Route::post('/articles/refresh', [ArticleController::class, 'refresh']);
        Route::post('/sources/{source}/toggle', [SourceController::class, 'toggle']);
    });
    
    // Sources endpoints
    Route::prefix('sources')->group(function () {
        Route::get('/', [SourceController::class, 'index']);
        Route::get('/categories', [SourceController::class, 'categories']);
        Route::get('/{source}/status', [SourceController::class, 'status']);
    });
    
    // User preferences endpoints
    Route::prefix('preferences')->group(function () {
        Route::get('/', [PreferenceController::class, 'show']);
        Route::post('/update', [PreferenceController::class, 'update']);
        Route::delete('/reset', [PreferenceController::class, 'reset']);
    });
});