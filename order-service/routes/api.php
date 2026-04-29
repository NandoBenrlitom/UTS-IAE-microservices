<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;

// Simulasi Database Transaksi
$orders = [];

// CONSUMER UTAMA: Menerima request transaksi, menarik data dari service lain
Route::post('/orders', function (Request $request) use (&$orders) {
    $userId = $request->input('user_id');
    $productId = $request->input('product_id');

    // Tarik data dari UserService (Port 8001)
    $userResponse = Http::get("http://127.0.0.1:8001/api/users/{$userId}");
    if ($userResponse->failed()) {
        return response()->json(['message' => 'User tidak valid'], 400);
    }

    // Tarik data dari ProductService (Port 8002)
    $productResponse = Http::get("http://127.0.0.1:8002/api/products/{$productId}");
    if ($productResponse->failed()) {
        return response()->json(['message' => 'Product tidak valid'], 400);
    }

    // Buat Transaksi
    $newOrder = [
        'order_id' => 'ORD-' . time(),
        'user' => $userResponse->json(),
        'product' => $productResponse->json(),
        'status' => 'Completed'
    ];

    // Dalam realita, $newOrder di-save ke DB MySQL di sini.
    return response()->json(['message' => 'Order berhasil!', 'data' => $newOrder], 201);
});

// PROVIDER: Menyediakan endpoint untuk histori
Route::get('/orders/user/{id}', function ($id) {
    // Simulasi data balikan
    return response()->json([
        ['order_id' => 'ORD-123', 'product_name' => 'Laptop EAI Pro', 'status' => 'Completed']
    ]);
});

Route::get('/orders/product/{id}', function ($id) {
    return response()->json([
        ['order_id' => 'ORD-123', 'buyer_name' => 'Budi Santoso', 'qty' => 1]
    ]);
})
