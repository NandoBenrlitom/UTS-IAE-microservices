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
});
