<?php
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;

// Data Statis (Simulasi Database MySQL)
$users = [
    ['id' => 1, 'name' => 'Budi Santoso', 'email' => 'budi@telkom.edu'],
    ['id' => 2, 'name' => 'Siti Aminah', 'email' => 'siti@telkom.edu']
];

// PROVIDER: Menyediakan data user
Route::get('/users/{id}', function ($id) use ($users) {
    $user = collect($users)->firstWhere('id', (int)$id);
    if (!$user) return response()->json(['message' => 'User not found'], 404);
    return response()->json($user);
});

// CONSUMER: Meminta histori dari OrderService (di Port 8003)
Route::get('/users/{id}/history', function ($id) {
    try {
        $response = Http::get("http://127.0.0.1:8003/api/orders/user/{$id}");
        return response()->json([
            'message' => 'Berhasil menarik histori',
            'data' => $response->json()
        ]);
    } catch (\Exception $e) {
        return response()->json(['message' => 'Gagal terhubung ke OrderService'], 500);
    }
});
