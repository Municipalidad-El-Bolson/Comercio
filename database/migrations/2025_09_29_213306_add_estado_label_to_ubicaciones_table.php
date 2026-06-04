<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ubicaciones', function (Blueprint $table) {
            if (!Schema::hasColumn('ubicaciones', 'estado_label')) {
                $table->string('estado_label', 120)
                      ->nullable()
                      ->after('estado'); // lo coloca al lado del campo estado
            }
        });
    }

    public function down(): void
    {
        Schema::table('ubicaciones', function (Blueprint $table) {
            if (Schema::hasColumn('ubicaciones', 'estado_label')) {
                $table->dropColumn('estado_label');
            }
        });
    }
};
