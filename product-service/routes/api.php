<?php
use App\Models\Product;
use Illuminate\Support\Facades\Http;

// PROVIDER: Menyediakan data produk
Route::get('/products/{id}', function ($id) {
    return Product::findOrFail($id);
});

// CONSUMER: Menarik data performa penjualan dari Order-Service
Route::get('/products/{id}/sales-stats', function ($id) {
    $response = Http::get("http://order-service:8000/api/orders/product/{$id}");
    return response()->json([
        'product' => Product::find($id),
        'sales_count' => count($response->json())
    ]);
});
