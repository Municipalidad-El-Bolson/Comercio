<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RubrosTableSeeder extends Seeder
{
    public function run(): void
    {
        // Detecta el esquema final y si no, esquemas intermedios (por si corrés en otra rama)
        if (Schema::hasColumn('rubros', 'mega_rubro')) {
            // ESQUEMA FINAL: mega_rubro + rubro_madre + subrubro (con unique triple)
            DB::table('rubros')->upsert([
                ['mega_rubro' => 'Comercio',  'rubro_madre' => 'Alimentos',   'subrubro' => 'Tienda'],
                ['mega_rubro' => 'Comercio',  'rubro_madre' => 'Alimentos',   'subrubro' => 'Supermercado'],
                ['mega_rubro' => 'Servicios', 'rubro_madre' => 'Belleza',     'subrubro' => 'Peluquería'],
                ['mega_rubro' => 'Servicios', 'rubro_madre' => 'Profesional', 'subrubro' => 'Estudio Contable'],
            ], ['mega_rubro','rubro_madre','subrubro'], []); // evita duplicados
            return;
        }

        if (Schema::hasColumn('rubros', 'rubro_madre') && Schema::hasColumn('rubros', 'subrubro')) {
            // ESQUEMA INTERMEDIO: rubro_madre + subrubro
            DB::table('rubros')->insert([
                ['rubro_madre' => 'Alimentos',   'subrubro' => 'Tienda'],
                ['rubro_madre' => 'Alimentos',   'subrubro' => 'Supermercado'],
                ['rubro_madre' => 'Belleza',     'subrubro' => 'Peluquería'],
                ['rubro_madre' => 'Profesional', 'subrubro' => 'Estudio Contable'],
            ]);
            return;
        }

        if (Schema::hasColumn('rubros', 'rubro')) {
            // ESQUEMA ANTIGUO: solo 'rubro'
            DB::table('rubros')->insert([
                ['rubro' => 'Tienda'],
                ['rubro' => 'Supermercado'],
                ['rubro' => 'Peluquería'],
                ['rubro' => 'Estudio Contable'],
            ]);
            return;
        }

        // Si no hay ninguna de las columnas esperadas:
        throw new \RuntimeException("La tabla 'rubros' no tiene columnas compatibles para el seeder.");
    }
}
