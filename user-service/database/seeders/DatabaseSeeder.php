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
        User::insert([
            ['id' => 1, 'name' => 'Budi Santoso', 'email' => 'budi@telkom.edu', 'password' => bcrypt('password')],
            ['id' => 2, 'name' => 'Siti Aminah', 'email' => 'siti@telkom.edu', 'password' => bcrypt('password')]
        ]);
        User::create(['name' => 'Nanda Pratama', 'email' => 'nanda@student.telkomuniversity.ac.id', 'password' => bcrypt('password')]);
    }
}
