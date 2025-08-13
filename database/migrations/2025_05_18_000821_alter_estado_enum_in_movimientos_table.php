<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('movimientos', function (Blueprint $table) {
            $table->enum('estado', [
                'En Proceso',
                'Observado',
                'Completo',
                'Rechazado',
                'Archivado',
                'Cancelado'
            ])->default('En Proceso')->change(); // usá ->addColumn si no existe
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('movimientos', function (Blueprint $table) {
            //
        });
    }
};
