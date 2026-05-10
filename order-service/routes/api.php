<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Order;
use App\Jobs\ProcessOrder;

// 1. Menerima request transaksi - PUBLISH ke Redis Queue (Asinkron)
Route::post('/orders', function (Request $request) {
    $validated = $request->validate([
        'user_id' => 'required|integer',
        'product_id' => 'required|integer',
    ]);

    // Simpan order dengan status PENDING agar client langsung dapat order_id
    $order = Order::create([
        'order_id' => 'ORD-' . time() . '-' . rand(100, 999),
        'user_id' => $validated['user_id'],
        'product_id' => $validated['product_id'],
        'total_price' => 0,
        'status' => 'PENDING',
    ]);

    // Kirim ke Redis Queue - akan diproses worker secara asinkron
    ProcessOrder::dispatch($order->id);

    return response()->json([
        'message' => 'Order diterima, sedang diproses secara asinkron',
        'order_id' => $order->order_id,
        'status' => $order->status,
    ], 202);
});

// 2. Cek status order tertentu (untuk polling hasil async)
Route::get('/orders/{orderId}', function (string $orderId) {
    $order = Order::where('order_id', $orderId)->first();
    if (!$order) {
        return response()->json(['message' => 'Order tidak ditemukan'], 404);
    }
    return response()->json($order);
});

// 3. Histori semua order
Route::get('/orders', function () {
    return response()->json(Order::orderByDesc('id')->get());
});

// 4. Histori transaksi user (Provider)
Route::get('/orders/user/{id}', function ($id) {
    return response()->json(Order::where('user_id', $id)->get());
});

// 5. Histori penjualan produk (Provider)
Route::get('/orders/product/{id}', function ($id) {
    return response()->json(Order::where('product_id', $id)->get());
});
