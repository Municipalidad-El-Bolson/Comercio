<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ubicaciones', function (Blueprint $table) {
            if (Schema::hasColumn('ubicaciones','dni')) $table->dropColumn('dni');
            if (Schema::hasColumn('ubicaciones','direccion')) $table->dropColumn('direccion');
            if (Schema::hasColumn('ubicaciones','tipo')) $table->dropColumn('tipo');
        });
    }

    public function down(): void
    {
        Schema::table('ubicaciones', function (Blueprint $table) {
            if (!Schema::hasColumn('ubicaciones','dni')) $table->string('dni')->nullable();
            if (!Schema::hasColumn('ubicaciones','direccion')) $table->string('direccion')->nullable();
            if (!Schema::hasColumn('ubicaciones','tipo')) $table->string('tipo')->nullable();
        });
    }
};

