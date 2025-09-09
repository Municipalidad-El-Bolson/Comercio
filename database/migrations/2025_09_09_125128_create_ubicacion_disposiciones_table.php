<?php

// database/migrations/2025_09_09_000003_create_ubicacion_disposiciones_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ubicacion_disposiciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ubicacion_id')->constrained('ubicaciones')->cascadeOnDelete();
            $table->string('numero', 60);
            $table->date('fecha')->nullable();
            $table->timestamps();
            $table->unique(['ubicacion_id','numero']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('ubicacion_disposiciones');
    }
};
