<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('comercio_estados')->insert([
            [
                'codigo'      => 'baja_oficio',
                'nombre'      => 'Baja de oficio',
                'aplica_fecha_alta' => false,
                'aplica_fecha_baja' => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'codigo'      => 'sin_efecto',
                'nombre'      => 'Expediente sin efecto',
                'aplica_fecha_alta' => false,
                'aplica_fecha_baja' => false,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('comercio_estados')
            ->whereIn('codigo', ['baja_oficio','sin_efecto'])
            ->delete();
    }
};
