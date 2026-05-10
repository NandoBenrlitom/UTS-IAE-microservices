<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

class ProcessOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $userId;
    protected $productId;

    // Menerima data dari request API
    public function __construct($userId, $productId)
    {
        $this->userId = $userId;
        $this->productId = $productId;
    }

    // Proses ini akan dijalankan oleh Redis di latar belakang
    public function handle()
    {
        Log::info("Memulai proses asinkron untuk User: {$this->userId}, Product: {$this->productId}");

        // Memanggil service lain secara asinkron di background menggunakan nama container docker
        $user = Http::get("http://user_service:8000/api/users/{$this->userId}");
        $product = Http::get("http://product_service:8000/api/products/{$this->productId}");

        // Jika data valid, simpan ke database order
        if ($user->successful() && $product->successful()) {
            Order::create([
                'user_id' => $this->userId,
                'product_id' => $this->productId,
                'total_price' => $product['price'],
                'status' => 'SUCCESS'
            ]);
            Log::info("Transaksi asinkron berhasil disimpan!");
        } else {
            Log::error("Data User atau Product tidak ditemukan.");
        }
    }
}
