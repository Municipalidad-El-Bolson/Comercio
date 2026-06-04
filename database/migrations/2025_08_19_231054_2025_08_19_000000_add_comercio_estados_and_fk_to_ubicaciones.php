<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';

        // 1) Tabla de estados
        if (!Schema::hasTable('comercio_estados')) {
            Schema::create('comercio_estados', function (Blueprint $table) {
                $table->string('codigo', 50)->primary(); // 'entramite' | 'vigente' | 'baja'
                $table->string('nombre', 100);
                $table->boolean('aplica_fecha_alta')->default(false);
                $table->boolean('aplica_fecha_baja')->default(false);
                $table->boolean('aplica_fecha_vto')->default(false);
                $table->boolean('habilita_seguimiento')->default(false);
                $table->unsignedSmallInteger('orden')->default(0);
                $table->timestamps();
            });
        }

        // 2) SEED de los 3 estados (ANTES de la FK)
        DB::table('comercio_estados')->upsert([
        [
            'codigo' => 'entramite','nombre' => 'En trámite',
            'aplica_fecha_alta' => false, 'aplica_fecha_baja' => false,
            'aplica_fecha_vto'  => false, 'habilita_seguimiento' => true, 'orden' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ],
        [
            'codigo' => 'vigente','nombre' => 'Vigente',
            'aplica_fecha_alta' => true,  'aplica_fecha_baja' => false,
            'aplica_fecha_vto'  => true,  'habilita_seguimiento' => false, 'orden' => 2,
            'created_at' => now(), 'updated_at' => now(),
        ],
        [
            'codigo' => 'irregular','nombre' => 'Irregular',
            'aplica_fecha_alta' => true,  'aplica_fecha_baja' => false,
            'aplica_fecha_vto'  => true,  'habilita_seguimiento' => true, 'orden' => 3,
            'created_at' => now(), 'updated_at' => now(),
        ],
        [
            'codigo' => 'baja','nombre' => 'Baja',
            'aplica_fecha_alta' => true,  'aplica_fecha_baja' => true,
            'aplica_fecha_vto'  => false, 'habilita_seguimiento' => false, 'orden' => 4,
            'created_at' => now(), 'updated_at' => now(),
        ],
    ], ['codigo'], ['nombre','aplica_fecha_alta','aplica_fecha_baja','aplica_fecha_vto','habilita_seguimiento','orden','updated_at']);


        // 3) Asegurar columnas en ubicaciones (sin doctrine/dbal)
        if (Schema::hasColumn('ubicaciones', 'estado')) {
            if (!$isSqlite) {
                DB::statement("ALTER TABLE `ubicaciones` MODIFY `estado` VARCHAR(50) NOT NULL DEFAULT 'entramite'");
            }
        } else {
            Schema::table('ubicaciones', function (Blueprint $table) {
                $table->string('estado', 50)->default('entramite')->after('id');
            });
        }

        if (!Schema::hasColumn('ubicaciones', 'fecha_vto')) {
            Schema::table('ubicaciones', function (Blueprint $table) {
                $table->date('fecha_vto')->nullable()->after('fecha_baja');
            });
        }

        // 4) Normalizar valores de estado a los 3 códigos válidos
        // - nulos / vacíos -> 'entramite'
        DB::statement("UPDATE `ubicaciones` SET `estado` = 'entramite' WHERE `estado` IS NULL OR `estado` = ''");

        // - cualquier valor NO incluido en comercio_estados -> 'entramite' (ej: 'irregular', etc.)
        if ($isSqlite) {
            DB::statement("
                UPDATE `ubicaciones`
                SET `estado` = 'entramite'
                WHERE `estado` NOT IN (SELECT `codigo` FROM `comercio_estados`)
            ");
        } else {
            DB::statement("
                UPDATE `ubicaciones` u
                LEFT JOIN `comercio_estados` ce ON ce.codigo = u.estado
                SET u.estado = 'entramite'
                WHERE ce.codigo IS NULL
            ");
        }

        // 5) FK idempotente
        if ($isSqlite) {
            return;
        }

        $fkName = 'ubicaciones_estado_fk';

        $fkExists = DB::selectOne("
            SELECT CONSTRAINT_NAME
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE()
              AND TABLE_NAME = 'ubicaciones'
              AND CONSTRAINT_NAME = ?
              AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ", [$fkName]);

        if ($fkExists) {
            DB::statement("ALTER TABLE `ubicaciones` DROP FOREIGN KEY `{$fkName}`");
        }

        $fkExists = DB::selectOne("
            SELECT CONSTRAINT_NAME
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE()
              AND TABLE_NAME = 'ubicaciones'
              AND CONSTRAINT_NAME = ?
              AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ", [$fkName]);

        if (!$fkExists) {
            DB::statement("
                ALTER TABLE `ubicaciones`
                ADD CONSTRAINT `{$fkName}`
                FOREIGN KEY (`estado`)
                REFERENCES `comercio_estados`(`codigo`)
                ON UPDATE CASCADE
                ON DELETE RESTRICT
            ");
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            Schema::dropIfExists('comercio_estados');

            return;
        }

        $fkName = 'ubicaciones_estado_fk';
        $fkExists = DB::selectOne("
            SELECT CONSTRAINT_NAME
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE()
              AND TABLE_NAME = 'ubicaciones'
              AND CONSTRAINT_NAME = ?
              AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ", [$fkName]);

        if ($fkExists) {
            DB::statement("ALTER TABLE `ubicaciones` DROP FOREIGN KEY `{$fkName}`");
        }

        Schema::dropIfExists('comercio_estados');
        // No elimino fecha_vto para no romper otras migrations/uso.
    }
};
