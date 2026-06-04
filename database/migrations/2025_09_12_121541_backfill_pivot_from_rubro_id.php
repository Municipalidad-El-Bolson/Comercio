<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $nowSql = DB::connection()->getDriverName() === 'sqlite'
            ? "CURRENT_TIMESTAMP"
            : "NOW()";

        // Inserta en pivot la dupla (ubicacion_id, rubro_id) si falta.
        DB::statement("
            INSERT INTO ubicacion_rubro (ubicacion_id, rubro_id, created_at, updated_at)
            SELECT u.id, u.rubro_id, {$nowSql}, {$nowSql}
            FROM ubicaciones u
            WHERE u.rubro_id IS NOT NULL
              AND NOT EXISTS (
                SELECT 1 FROM ubicacion_rubro ur
                WHERE ur.ubicacion_id = u.id AND ur.rubro_id = u.rubro_id
              )
        ");
    }

    public function down(): void
    {
        // No hacemos rollback (no sabemos cuáles estaban antes).
    }
};
