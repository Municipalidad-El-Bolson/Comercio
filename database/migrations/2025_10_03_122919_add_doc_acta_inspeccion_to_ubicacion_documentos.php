<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ubicacion_documentos', function (Blueprint $table) {
            if (!Schema::hasColumn('ubicacion_documentos', 'doc_acta_inspeccion')) {
                $table->boolean('doc_acta_inspeccion')
                      ->default(false)
                      ->after('doc_pago_baja');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ubicacion_documentos', function (Blueprint $table) {
            if (Schema::hasColumn('ubicacion_documentos', 'doc_acta_inspeccion')) {
                $table->dropColumn('doc_acta_inspeccion');
            }
        });
    }
};
