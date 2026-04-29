<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/register', [UserController::class, 'register']);
    Route::post('/login', [UserController::class, 'login']);
});

Route::middleware('auth:api')->group(function () {
    Route::get('/auth/profile', [UserController::class, 'profile']);
    Route::post('/auth/logout', [UserController::class, 'logout']);
});

Route::get('/users/{id}', [UserController::class, 'show'])->whereNumber('id');
Route::get('/users/{id}/history', [UserController::class, 'history'])->whereNumber('id');
