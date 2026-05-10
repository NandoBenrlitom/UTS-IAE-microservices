<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['id' => 101, 'name' => 'Laptop EAI Pro', 'price' => 15000000],
            ['id' => 102, 'name' => 'Keyboard Mechanical', 'price' => 800000],
            ['id' => 103, 'name' => 'Monitor 27 inch 4K', 'price' => 4500000],
        ];

        foreach ($products as $p) {
            Product::updateOrCreate(
                ['id' => $p['id']],
                ['name' => $p['name'], 'price' => $p['price']]
            );
        }
    }
}
