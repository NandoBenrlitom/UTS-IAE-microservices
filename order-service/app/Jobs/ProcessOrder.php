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

    public int $tries = 3;
    public int $backoff = 5;

    protected int $orderId;

    public function __construct(int $orderId)
    {
        $this->orderId = $orderId;
    }

    public function handle(): void
    {
        $order = Order::find($this->orderId);
        if (!$order) {
            Log::error("ProcessOrder: order #{$this->orderId} tidak ditemukan");
            return;
        }

        Log::info("ProcessOrder: mulai memproses order {$order->order_id}");

        // Simulasi proses async (misal: validasi pembayaran)
        sleep(2);

        $userResponse = Http::timeout(5)->get("http://user-service:8000/api/users/{$order->user_id}");
        $productResponse = Http::timeout(5)->get("http://product-service:8000/api/products/{$order->product_id}");

        if ($userResponse->successful() && $productResponse->successful()) {
            $product = $productResponse->json();
            $order->update([
                'total_price' => $product['price'] ?? 0,
                'status' => 'SUCCESS',
            ]);
            Log::info("ProcessOrder: order {$order->order_id} SUCCESS");
        } else {
            $order->update(['status' => 'FAILED']);
            Log::error("ProcessOrder: order {$order->order_id} FAILED - user atau product tidak valid");
        }
    }

    public function failed(\Throwable $e): void
    {
        $order = Order::find($this->orderId);
        if ($order) {
            $order->update(['status' => 'FAILED']);
        }
        Log::error("ProcessOrder gagal total: " . $e->getMessage());
    }
}
