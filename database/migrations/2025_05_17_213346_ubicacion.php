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
        Schema::create('ubicaciones', function (Blueprint $table) {
            $table->id();
            $table->string('razon_social');
            $table->string('apellido');
            $table->string('nombres');
            $table->integer('dni');
            $table->foreignId('rubro_id')->constrained('rubros')->onDelete('cascade');
            $table->string('direccion');
            $table->double('latitud', 10, 6)->nullable();
            $table->double('longitud', 10, 6)->nullable();
            $table->boolean('habilitado')->default(true);
            $table->enum('estado', ['normal', 'irregular', 'faltadoc'])->default('normal');
            $table->string('tipo');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ubicacions');
    }
};
