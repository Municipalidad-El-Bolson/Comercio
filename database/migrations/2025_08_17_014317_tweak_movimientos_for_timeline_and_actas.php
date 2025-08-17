<?php

// database/migrations/xxxx_xx_xx_xxxxxx_tweak_movimientos_for_timeline_and_actas.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('movimientos', function (Blueprint $table) {
            if (!Schema::hasColumn('movimientos', 'tipo')) {
                $table->string('tipo')->default('acta')->index()->after('ubicacion_id'); // 'acta' | 'timeline'
            }
            if (Schema::hasColumn('movimientos', 'titulo')) {
                $table->string('titulo')->nullable()->change();
            }
            if (Schema::hasColumn('movimientos', 'descripcion')) {
                $table->text('descripcion')->nullable()->change();
            }
            if (!Schema::hasColumn('movimientos', 'etapa')) {
                $table->string('etapa')->nullable()->index();
            }
            if (!Schema::hasColumn('movimientos', 'fecha')) {
                $table->date('fecha')->nullable()->index();
            }
            if (!Schema::hasColumn('movimientos', 'archivo')) {
                $table->string('archivo')->nullable();
            }
            if (!Schema::hasColumn('movimientos', 'estado')) {
                $table->string('estado')->nullable();
            }
        });
    }

    public function down(): void {
        Schema::table('movimientos', function (Blueprint $table) {
            // Revertir sólo si querés estrictamente volver atrás
            // $table->dropColumn(['tipo','etapa','fecha','archivo','estado']);
        });
    }
};
