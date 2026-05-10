<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Order-service tidak menyimpan user / product, hanya transaksi.
        // Order dibuat lewat endpoint POST /api/orders (asinkron).
    }
}
