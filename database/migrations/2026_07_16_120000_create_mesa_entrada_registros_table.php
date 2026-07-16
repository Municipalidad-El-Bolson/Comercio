<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mesa_entrada_registros', function (Blueprint $table) {
            $table->id();
            $table->date('fecha')->index();
            $table->unsignedBigInteger('nro_ingreso')->index();
            $table->string('titular_razon')->index();
            $table->string('hc', 100)->nullable()->index();
            $table->json('documentos');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('sender_name')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mesa_entrada_registros');
    }
};
