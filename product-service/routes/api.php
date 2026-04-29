<?php
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;

$products = [
    ['id' => 101, 'name' => 'Laptop EAI Pro', 'price' => 15000000],
    ['id' => 102, 'name' => 'Keyboard Mechanical', 'price' => 800000]
];

// PROVIDER: Menyediakan data produk
Route::get('/products/{id}', function ($id) use ($products) {
    $product = collect($products)->firstWhere('id', (int)$id);
    if (!$product) return response()->json(['message' => 'Product not found'], 404);
    return response()->json($product);
});

// CONSUMER: Meminta data penjualan dari OrderService (di Port 8003)
Route::get('/products/{id}/sales', function ($id) {
    try {
        $response = Http::get("http://127.0.0.1:8003/api/orders/product/{$id}");
        return response()->json([
            'message' => 'Berhasil menarik data penjualan',
            'data' => $response->json()
        ]);
    } catch (\Exception $e) {
        return response()->json(['message' => 'Gagal terhubung ke OrderService'], 500);
    }
});
