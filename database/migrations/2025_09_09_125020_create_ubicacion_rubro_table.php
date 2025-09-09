<?php

// database/migrations/2025_09_09_000001_create_ubicacion_rubro_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        Schema::create('ubicacion_rubro', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ubicacion_id')->constrained('ubicaciones')->cascadeOnDelete();
            $table->foreignId('rubro_id')->constrained('rubros');
            $table->unsignedInteger('orden')->default(1);
            $table->timestamps();
            $table->unique(['ubicacion_id','rubro_id']);
        });

        // Backfill desde la columna existente
        DB::statement("
            INSERT INTO ubicacion_rubro (ubicacion_id, rubro_id, orden, created_at, updated_at)
            SELECT id, rubro_id, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
            FROM ubicaciones
            WHERE rubro_id IS NOT NULL
        ");
    }
    public function down(): void {
        Schema::dropIfExists('ubicacion_rubro');
    }
};
