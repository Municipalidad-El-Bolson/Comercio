<?php

namespace Database\Seeders;

use App\Models\Rubro;
use Illuminate\Database\Seeder;

class RubrosTableSeeder extends Seeder
{
    public function run(): void
    {
        $rubros = ['Tienda', 'Hoteles', 'Supermercado', 'Inmobiliaria', 'Otros'];

        foreach ($rubros as $nombre) {
            Rubro::create(['rubro' => $nombre]);
        }
    }
}
