<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ubicacion_id')->constrained('ubicaciones')->onDelete('cascade');
            $table->string('titulo');
            $table->string('descripcion');
            $table->enum('estado', [
                'En Proceso',
                'Observado',
                'Completo',
                'Rechazado',
                'Archivado',
                'Cancelado'
            ])->default('En Proceso');
            $table->string('archivo')->nullable(); // guarda el path
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimientos');
    }
};
