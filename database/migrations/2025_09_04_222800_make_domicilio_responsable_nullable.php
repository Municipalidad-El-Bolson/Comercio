<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ubicaciones', function (Blueprint $table) {
            $table->string('domicilio_responsable', 160)->nullable()->default(null)->change();
            $table->string('nomenclatura', 80)->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('ubicaciones', function (Blueprint $table) {
            // revertí según tu esquema anterior
            $table->string('domicilio_responsable', 160)->nullable(false)->change();
            $table->string('nomenclatura', 80)->nullable(false)->change();
        });
    }
};
