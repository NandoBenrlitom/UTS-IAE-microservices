<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        \App\Models\Product::insert([
            ['id' => 101, 'name' => 'Laptop EAI Pro', 'price' => 15000000],
            ['id' => 102, 'name' => 'Keyboard Mechanical', 'price' => 800000]
        ]);
    }
}
