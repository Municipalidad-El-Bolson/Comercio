<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $values = [
            'nombre' => '040/25',
            'aplica_fecha_alta' => 1,
            'aplica_fecha_baja' => 0,
            'aplica_fecha_vto' => 1,
            'habilita_seguimiento' => 1,
            'orden' => 5,
            'updated_at' => now(),
        ];

        if (DB::table('comercio_estados')->where('codigo', '040')->exists()) {
            DB::table('comercio_estados')->where('codigo', '040')->update($values);

            return;
        }

        DB::table('comercio_estados')->insert($values + [
            'codigo' => '040',
            'created_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('comercio_estados')->where('codigo', '040')->delete();
    }
};
