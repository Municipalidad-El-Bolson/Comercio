<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ubicacion_documentos', function (Blueprint $table) {
            // Documentos para bajas
            if (!Schema::hasColumn('ubicacion_documentos', 'doc_pago_baja')) {
                $table->boolean('doc_pago_baja')->default(false)->after('id');
            }
            if (!Schema::hasColumn('ubicacion_documentos', 'doc_libre_deuda_municipal')) {
                $table->boolean('doc_libre_deuda_municipal')->default(false)->after('doc_pago_baja');
            }
            if (!Schema::hasColumn('ubicacion_documentos', 'doc_acta_inspeccion')) {
                $table->boolean('doc_acta_inspeccion')->default(false)->after('doc_libre_deuda_municipal');
            }
            if (!Schema::hasColumn('ubicacion_documentos', 'doc_nota_baja')) {
                $table->boolean('doc_nota_baja')->default(false)->after('doc_acta_inspeccion');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ubicacion_documentos', function (Blueprint $table) {
            if (Schema::hasColumn('ubicacion_documentos', 'doc_pago_baja')) {
                $table->dropColumn('doc_pago_baja');
            }
            if (Schema::hasColumn('ubicacion_documentos', 'doc_libre_deuda_municipal')) {
                $table->dropColumn('doc_libre_deuda_municipal');
            }
            if (Schema::hasColumn('ubicacion_documentos', 'doc_acta_inspeccion')) {
                $table->dropColumn('doc_acta_inspeccion');
            }
            if (Schema::hasColumn('ubicacion_documentos', 'doc_nota_baja')) {
                $table->dropColumn('doc_nota_baja');
            }
        });
    }
};
