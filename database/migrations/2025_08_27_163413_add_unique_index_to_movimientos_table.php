<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movimientos', function (Blueprint $table) {
            $table->unique(
                ['ubicacion_id', 'etapa'],
                'mov_ubicacion_etapa_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('movimientos', function (Blueprint $table) {
            $table->dropUnique('mov_ubicacion_etapa_unique');
        });
    }
};
