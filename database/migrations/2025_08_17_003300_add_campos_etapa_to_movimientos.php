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
            if (!Schema::hasColumn('movimientos', 'etapa')) {
            $table->string('etapa')->nullable()->index();
        }
        if (!Schema::hasColumn('movimientos', 'fecha')) {
            $table->date('fecha')->nullable()->index();
        }
        if (!Schema::hasColumn('movimientos', 'observacion')) {
            $table->text('observacion')->nullable();
        }
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
