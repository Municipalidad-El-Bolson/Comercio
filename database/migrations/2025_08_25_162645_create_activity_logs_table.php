<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 100);                 // p.ej: 'login', 'comercio.create', 'comercio.update'
            $table->string('entity_type')->nullable();     // p.ej: App\Models\Comercio
            $table->string('entity_id')->nullable();       // id del registro afectado
            $table->string('ip', 45)->nullable();
            $table->string('method', 10)->nullable();      // GET/POST/PUT/DELETE
            $table->string('path')->nullable();            // /comercios/123
            $table->json('meta')->nullable();              // payload mínimo o difs
            $table->timestamps();                          // created_at = CUÁNDO
            $table->index(['user_id', 'created_at']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('audit_logs');
    }
};
