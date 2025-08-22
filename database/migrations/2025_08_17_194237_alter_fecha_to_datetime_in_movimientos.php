<?php

// database/migrations/xxxx_xx_xx_xxxxxx_alter_fecha_to_datetime_in_movimientos.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('movimientos', function (Blueprint $table) {
            $table->dateTime('fecha')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('movimientos', function (Blueprint $table) {
            $table->date('fecha')->nullable()->change();
        });
    }
};
