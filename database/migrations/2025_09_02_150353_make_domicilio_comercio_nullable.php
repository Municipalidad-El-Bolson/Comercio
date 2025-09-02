<?php

return new class extends \Illuminate\Database\Migrations\Migration {
    public function up(): void
    {
        \Illuminate\Support\Facades\Schema::table('ubicaciones', function ($table) {
            $table->string('domicilio_comercio')->nullable()->change();
        });
    }

    public function down(): void
    {
        \Illuminate\Support\Facades\Schema::table('ubicaciones', function ($table) {
            $table->string('domicilio_comercio')->nullable(false)->change();
        });
    }
};