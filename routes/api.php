<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PostController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('api')->group(function () {
    // Additional API routes (must come BEFORE apiResource to avoid conflicts)
    Route::get('/posts/stats', [PostController::class, 'stats']);
    Route::get('/posts/autocomplete', [PostController::class, 'autocomplete']);
    Route::post('/posts/bulk', [PostController::class, 'bulkStore']);
    Route::post('/posts/search', [PostController::class, 'search']);
    
    // Posts CRUD operations
    Route::apiResource('posts', PostController::class);
}); 