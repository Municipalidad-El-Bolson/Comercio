<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rubros', function (Blueprint $table) {
            $table->id();
            // Campo básico 'rubro' para que la 2025_08_13_* pueda luego agregar madre/subrubro y quitar este
            $table->string('rubro')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rubros');
    }
};
