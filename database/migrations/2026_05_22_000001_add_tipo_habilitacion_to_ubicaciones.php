<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('ubicaciones', 'tipo_habilitacion')) {
            Schema::table('ubicaciones', function (Blueprint $table) {
                $table->string('tipo_habilitacion', 20)->default('definitiva')->after('estado');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('ubicaciones', 'tipo_habilitacion')) {
            Schema::table('ubicaciones', function (Blueprint $table) {
                $table->dropColumn('tipo_habilitacion');
            });
        }
    }
};
