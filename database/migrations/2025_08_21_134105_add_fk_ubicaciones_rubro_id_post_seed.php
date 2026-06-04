<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';

        // Asegurar que la columna admita NULL por si hay huérfanos
        try {
            Schema::table('ubicaciones', function (Blueprint $table) {
                $table->unsignedBigInteger('rubro_id')->nullable()->change();
            });
        } catch (\Throwable $e) {
            // ignorar si no se puede change (sin doctrine) o ya es nullable
        }

        // Nullificar referencias a rubros inexistentes
        if ($isSqlite) {
            DB::statement("
                UPDATE ubicaciones
                SET rubro_id = NULL
                WHERE rubro_id IS NOT NULL
                  AND rubro_id NOT IN (SELECT id FROM rubros)
            ");

            return;
        }

        DB::statement("
            UPDATE ubicaciones u
            LEFT JOIN rubros r ON r.id = u.rubro_id
            SET u.rubro_id = NULL
            WHERE r.id IS NULL
        ");

        // Limpiar cualquier FK previa residual por nombre desconocido
        $fk = DB::selectOne("
            SELECT CONSTRAINT_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'ubicaciones'
              AND COLUMN_NAME = 'rubro_id'
              AND REFERENCED_TABLE_NAME IS NOT NULL
            LIMIT 1
        ");
        if ($fk && isset($fk->CONSTRAINT_NAME)) {
            DB::statement("ALTER TABLE `ubicaciones` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
        }

        // Agregar la FK final
        Schema::table('ubicaciones', function (Blueprint $table) {
            $table->foreign('rubro_id', 'ubicaciones_rubro_id_foreign')
                  ->references('id')->on('rubros')
                  ->cascadeOnUpdate()
                  ->restrictOnDelete(); // o ->nullOnDelete() si preferís
        });
    }

    public function down(): void
    {
        Schema::table('ubicaciones', function (Blueprint $table) {
            try { $table->dropForeign('ubicaciones_rubro_id_foreign'); } catch (\Throwable $e) {}
        });
    }
};
