<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Soltar la FK en ubicaciones.rubro_id (si existe, cualquiera sea su nombre)
        if (Schema::hasTable('ubicaciones') && Schema::hasColumn('ubicaciones', 'rubro_id')) {
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

            // evita residuos de índices.
            try {
                DB::statement("ALTER TABLE `ubicaciones` DROP INDEX `{$fk->CONSTRAINT_NAME}`");
            } catch (\Throwable $e) {
                // ignorar si no existe
            }
        }

        // 2) Dropear la tabla rubros si existe (ya no hay FK colgando)
        DB::statement('DROP TABLE IF EXISTS `rubros`');

        // 3) Crear rubros con el nuevo diseño
        Schema::create('rubros', function (Blueprint $table) {
            $table->id(); // BIGINT UNSIGNED AUTO_INCREMENT
            $table->string('mega_rubro');
            $table->string('rubro_madre');
            $table->string('subrubro');
            $table->timestamps();
            $table->unique(['mega_rubro', 'rubro_madre', 'subrubro'], 'rubros_unique_triple');
        });

    }

    public function down(): void
    {
        // Intentar soltar FK si estuviera
        if (Schema::hasTable('ubicaciones') && Schema::hasColumn('ubicaciones', 'rubro_id')) {
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
        }

        Schema::dropIfExists('rubros');
    }
};
