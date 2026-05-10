<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['id' => 1, 'name' => 'Budi Santoso', 'email' => 'budi@telkom.edu'],
            ['id' => 2, 'name' => 'Siti Aminah', 'email' => 'siti@telkom.edu'],
            ['id' => 3, 'name' => 'Nanda Pratama', 'email' => 'nanda@student.telkomuniversity.ac.id'],
        ];

        foreach ($users as $u) {
            User::updateOrCreate(
                ['email' => $u['email']],
                ['id' => $u['id'], 'name' => $u['name'], 'password' => bcrypt('password')]
            );
        }
    }
}
