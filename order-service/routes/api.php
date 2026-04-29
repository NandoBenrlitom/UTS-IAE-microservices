<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use App\Models\Order;

// 1. Menerima request transaksi & cek ke service lain (Consumer)
Route::post('/orders', function (Request $request) {
    $userId = $request->user_id;
    $productId = $request->product_id;

    // Cek User ke User-Service (Port 8001)
    $userResponse = Http::get("http://127.0.0.1:8001/api/users/{$userId}");
    if ($userResponse->failed()) {
        return response()->json(['message' => 'Data User tidak ditemukan'], 404);
    }

    // Cek Product ke Product-Service (Port 8002)
    $productResponse = Http::get("http://127.0.0.1:8002/api/products/{$productId}");
    if ($productResponse->failed()) {
        return response()->json(['message' => 'Data Product tidak ditemukan'], 404);
    }

    // Simpan Transaksi
    $order = Order::create([
        'order_id' => 'ORD-' . time() . '-' . rand(100, 999),
        'user_id' => $userId,
        'product_id' => $productId,
        'status' => 'SUCCESS'
    ]);

    return response()->json(['message' => 'Transaksi Berhasil', 'detail' => $order], 201);
});

// 2. Menyediakan histori transaksi user (Provider)
Route::get('/orders/user/{id}', function ($id) {
    return response()->json(Order::where('user_id', $id)->get());
});

// 3. Menyediakan histori penjualan produk (Provider)
Route::get('/orders/product/{id}', function ($id) {
    return response()->json(Order::where('product_id', $id)->get());
});
