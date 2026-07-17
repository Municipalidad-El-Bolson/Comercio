<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ubicaciones', function (Blueprint $table) {
            $table->boolean('baja_temporaria')->default(false)->index();
        });

        DB::table('ubicaciones')
            ->where(function ($query) {
                $query->where('estado', 'baja')->orWhere('estado_base', 'baja');
            })
            ->where('tipo_hab', 'prev')
            ->update(['baja_temporaria' => true]);
    }

    public function down(): void
    {
        Schema::table('ubicaciones', fn (Blueprint $table) => $table->dropColumn('baja_temporaria'));
    }
};
