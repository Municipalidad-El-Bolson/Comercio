<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ubicaciones', function (Blueprint $table) {
            $table->date('suspension_tasas_desde')->nullable();
            $table->date('suspension_tasas_hasta')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('ubicaciones', function (Blueprint $table) {
            $table->dropColumn(['suspension_tasas_desde', 'suspension_tasas_hasta']);
        });
    }
};
