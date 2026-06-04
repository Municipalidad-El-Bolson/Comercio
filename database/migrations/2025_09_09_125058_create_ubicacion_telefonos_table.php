<?php

// database/migrations/2025_09_09_000002_create_ubicacion_telefonos_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        Schema::create('ubicacion_telefonos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ubicacion_id')->constrained('ubicaciones')->cascadeOnDelete();
            $table->string('telefono', 40);
            $table->string('tipo', 20)->nullable(); // ej. fijo/cel/whatsapp (opcional)
            $table->timestamps();
            $table->unique(['ubicacion_id','telefono']);
        });

        // Backfill simple (si existía una sola columna)
        if (Schema::hasColumn('ubicaciones','telefono')) {
            DB::statement("
                INSERT INTO ubicacion_telefonos (ubicacion_id, telefono, created_at, updated_at)
                SELECT id, telefono, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                FROM ubicaciones
                WHERE telefono IS NOT NULL AND telefono <> ''
            ");
        }
    }
    public function down(): void {
        Schema::dropIfExists('ubicacion_telefonos');
    }
};
