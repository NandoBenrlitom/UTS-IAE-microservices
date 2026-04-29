<<<<<<< HEAD
use App\Models\User;
use Illuminate\Support\Facades\Http;

// PROVIDER: Menyediakan data user untuk Service lain
Route::get('/users/{id}', function ($id) {
    return User::findOrFail($id);
});

// CONSUMER: Menarik data histori dari Order-Service
Route::get('/users/{id}/history', function ($id) {
    // Memanggil Order-Service secara langsung
    $response = Http::get("http://127.0.0.1:8003/api/orders/user/{$id}");
    return response()->json([
        'user' => User::find($id),
        'order_history' => $response->json()
    ]);
=======
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
>>>>>>> 1c268f46641945a4a0c1cc130ce868befb2e46a1
});

Route::get('/users/{id}', [UserController::class, 'show'])->whereNumber('id');
Route::get('/users/{id}/history', [UserController::class, 'history'])->whereNumber('id');
