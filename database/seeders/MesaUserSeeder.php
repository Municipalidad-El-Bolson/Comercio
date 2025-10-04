<?php
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class MesaUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'mesa@tuapp.local'],
            [
                'name' => 'Mesa de Entrada',
                'password' => Hash::make('cambiame'),
                'role' => 'mesa',
            ]
        );
    }
}