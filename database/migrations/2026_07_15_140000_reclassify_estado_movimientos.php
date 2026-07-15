<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('movimientos')
            ->where('tipo', 'acta')
            ->where('etapa', 'estado')
            ->whereNull('tipo_acta')
            ->where(function ($query) {
                $query->whereNull('titulo')->orWhereRaw("TRIM(titulo) = ''");
            })
            ->update(['tipo' => 'estado']);
    }

    public function down(): void
    {
        // No se revierte: volver a clasificarlos como actas reintroduciría el error.
    }
};
