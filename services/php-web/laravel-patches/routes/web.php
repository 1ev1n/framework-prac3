<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\RateLimitMiddleware;

Route::get('/', fn() => redirect('/dashboard'));

// Панели
Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index']);
Route::get('/iss',      [\App\Http\Controllers\IssController::class, 'index']);
Route::get('/osdr',     [\App\Http\Controllers\OsdrController::class, 'index']);
Route::get('/jwst',     [\App\Http\Controllers\JwstController::class, 'index']);
Route::get('/astro',    [\App\Http\Controllers\AstroController::class, 'index']);

// Прокси к rust_iss (с Rate-Limit: 60 запросов в минуту)
Route::middleware([RateLimitMiddleware::class . ':60,1'])->group(function () {
    Route::get('/api/iss/last',  [\App\Http\Controllers\ProxyController::class, 'last']);
    Route::get('/api/iss/trend', [\App\Http\Controllers\ProxyController::class, 'trend']);
});

// JWST галерея (JSON) (с Rate-Limit: 30 запросов в минуту)
// Временно отключаем middleware для отладки
Route::get('/api/jwst/feed', [\App\Http\Controllers\JwstController::class, 'feed']);

// Astro API (с Rate-Limit: 20 запросов в минуту)
Route::middleware([RateLimitMiddleware::class . ':20,1'])->group(function () {
    Route::get("/api/astro/events", [\App\Http\Controllers\AstroController::class, "events"]);
});

// CSV visualization (с Rate-Limit: 30 запросов в минуту)
Route::middleware([RateLimitMiddleware::class . ':30,1'])->group(function () {
    Route::get('/csv', [\App\Http\Controllers\CsvController::class, 'index']);
    Route::get('/csv/view/{filename}', [\App\Http\Controllers\CsvController::class, 'view']);
    Route::get('/csv/export/{filename}', [\App\Http\Controllers\CsvController::class, 'exportXlsx']);
});

// CMS
Route::get('/page/{slug}', [\App\Http\Controllers\CmsController::class, 'page']);
