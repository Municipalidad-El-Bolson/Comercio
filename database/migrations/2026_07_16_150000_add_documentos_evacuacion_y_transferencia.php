<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        foreach (['Plan de evacuación', 'Croquis de plan de evacuación', 'Nota de transferencia de razón social a familiar'] as $nombre) {
            DB::table('documentos')->updateOrInsert(
                ['nombre' => $nombre],
                ['activo' => true, 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }

    public function down(): void
    {
        DB::table('documentos')->whereIn('nombre', ['Plan de evacuación', 'Croquis de plan de evacuación', 'Nota de transferencia de razón social a familiar'])->delete();
    }
};
