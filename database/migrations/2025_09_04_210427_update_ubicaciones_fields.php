<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ubicaciones', function (Blueprint $table) {
            // fecha_vto manual y opcional
            $table->date('fecha_vto')->nullable()->change();

            // Aseguramos que soporte guiones / longitudes cómodas
            $table->string('dni_cuit', 32)->change();

            // (OPCIONAL) si de verdad no querés almacenar estos campos:
            // OJO: hacé un backup primero.
            // $table->dropColumn(['domicilio_responsable','nomenclatura']);
        });
    }

    public function down(): void
    {
        Schema::table('ubicaciones', function (Blueprint $table) {
            // revertí a tu tipo anterior si lo necesitás
            // $table->string('dni_cuit', 11)->change();
            // $table->date('fecha_vto')->nullable(false)->change();
            // $table->string('domicilio_responsable')->nullable();
            // $table->string('nomenclatura')->nullable();
        });
    }
};