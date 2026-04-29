<?php
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;

// CONSUMER UTAMA: Menerima request transaksi, menarik data dari service lain
Route::post('/orders', function (Request $request) {
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

    // Buat Transaksi di DB MySQL
    $newOrder = Order::create([
        'order_id' => 'ORD-' . time(),
        'user_id' => $userId,
        'product_id' => $productId,
        'status' => 'Completed'
    ]);

    return response()->json([
        'message' => 'Order berhasil!',
        'data' => [
            'order' => $newOrder,
            'user' => $userResponse->json(),
            'product' => $productResponse->json()
        ]
    ], 201);
});

// PROVIDER: Menyediakan endpoint untuk histori
Route::get('/orders/user/{id}', function ($id) {
    $orders = Order::where('user_id', $id)->get();
    return response()->json($orders);
});

Route::get('/orders/product/{id}', function ($id) {
    $orders = Order::where('product_id', $id)->get();
    return response()->json($orders);
});
