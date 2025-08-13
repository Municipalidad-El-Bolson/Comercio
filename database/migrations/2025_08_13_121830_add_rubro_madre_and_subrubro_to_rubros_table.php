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
        Schema::table('rubros', function (Blueprint $table) {
            $table->string('rubro_madre')->after('id');
            $table->string('subrubro')->after('rubro_madre');
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rubros', function (Blueprint $table) {
            $table->dropColumn(['rubro_madre', 'subrubro']);
        });
    }
};
