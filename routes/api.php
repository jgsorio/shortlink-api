<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ShortlinkController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::get('/unauthorized', fn () => abort(401))->name('login');
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/shortlink', [ShortlinkController::class, 'index']);
    Route::post('/shortlink', [ShortlinkController::class, 'store']);
    Route::delete('/shortlink/{shortLink}', [ShortlinkController::class, 'destroy']);
});

Route::get('/shortlink/{short_url}', [ShortlinkController::class, 'show']);