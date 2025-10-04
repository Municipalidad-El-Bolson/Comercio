<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(\Database\Seeders\RubrosTableSeeder::class);
        $this->call(\Database\Seeders\AdminUserSeeder::class);
        $this->call(\Database\Seeders\DocumentoSeeder::class);
        $this->call(\Database\Seeders\MesaUserSeeder::class);
    }
}
