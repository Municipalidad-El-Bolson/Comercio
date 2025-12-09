<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ubicaciones', function (Blueprint $table) {
            $table->integer('camping_fogones')->nullable();
            $table->integer('camping_dormis')->nullable();
            $table->string('camping_otros_servicios')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('ubicaciones', function (Blueprint $table) {
            $table->dropColumn([
                'camping_fogones',
                'camping_dormis',
                'camping_otros_servicios',
            ]);
        });
    }

};
