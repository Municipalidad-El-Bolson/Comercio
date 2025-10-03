<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Inserta o actualiza el estado 040
        DB::table('comercio_estados')->updateOrInsert(
            ['codigo' => '040'],
            [
                'nombre'               => '040/25',   // o '040' si preferís
                'aplica_fecha_alta'    => 1,
                'aplica_fecha_baja'    => 0,
                'aplica_fecha_vto'     => 1,          // aplica (tu regla puede hacerlo opcional)
                'habilita_seguimiento' => 1,
                'orden'                => 5,
                'updated_at'           => now(),
                'created_at'           => DB::raw('COALESCE(created_at, NOW())'),
            ]
        );
    }

    public function down(): void
    {
        DB::table('comercio_estados')->where('codigo', '040')->delete();
    }
};

