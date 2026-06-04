<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends \Illuminate\Database\Migrations\Migration {
    public function up(): void
    {
        Schema::table('ubicaciones', function (Blueprint $table) {
            $table->string('apellido', 60)->nullable()->change();
            $table->string('nombres', 80)->nullable()->change();
            // opcional: si querés permitir null en razón social (para personas físicas)
            // $table->string('razon_social', 120)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('ubicaciones', function (Blueprint $table) {
            $table->string('apellido', 60)->nullable(false)->change();
            $table->string('nombres', 80)->nullable(false)->change();
            // $table->string('razon_social', 120)->nullable(false)->change();
        });
    }
};
