<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'muranonicolas12@gmail.com'],
            ['name' => 'Administrador', 'password' => Hash::make('Franzkafka12'), 'role' => 'admin']
        );
    }
}

