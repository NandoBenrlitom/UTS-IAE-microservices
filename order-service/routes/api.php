<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Jobs\ProcessOrder;
use App\Models\Order;

// Endpoint pembuat transaksi (ASINKRON)
Route::post('/orders', function (Request $request) {
    // Melemparkan tugas ke antrean Redis
    ProcessOrder::dispatch($request->user_id, $request->product_id);

    // Langsung merespon ke user tanpa harus menunggu validasi selesai
    return response()->json([
        'message' => 'Transaksi sedang diproses di latar belakang via Redis',
        'status' => 'Processing'
    ], 202);
});

// Endpoint tambahan untuk mengecek apakah order berhasil masuk database
Route::get('/orders', function () {
    return response()->json(Order::all());
});
