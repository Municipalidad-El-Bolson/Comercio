<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ubicaciones', function (Blueprint $table) {
            if (Schema::hasColumn('ubicaciones','fecha_alta')) $table->dateTime('fecha_alta')->nullable()->change();
            if (Schema::hasColumn('ubicaciones','fecha_baja')) $table->dateTime('fecha_baja')->nullable()->change();
        });
    }

    public function down(): void {}
};
