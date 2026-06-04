<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ubicacion_documentos', function (Blueprint $table) {
            // Crear columnas canónicas si no existen (sin ->after())
            if (!Schema::hasColumn('ubicacion_documentos', 'doc_afip_constancia')) {
                $table->boolean('doc_afip_constancia')->default(false);
            }
            if (!Schema::hasColumn('ubicacion_documentos', 'doc_recaudacion_rn')) {
                $table->boolean('doc_recaudacion_rn')->default(false);
            }
        });

        // Backfill CONDICIONAL (solo si existen las columnas fuente)
        $hasAfipFisica   = Schema::hasColumn('ubicacion_documentos', 'doc_afip_constancia_fisica');
        $hasAfipJuridica = Schema::hasColumn('ubicacion_documentos', 'doc_afip_constancia_juridica');
        $hasRecaudacion  = Schema::hasColumn('ubicacion_documentos', 'doc_constancia_recaudacion');

        if ($hasAfipFisica || $hasAfipJuridica) {
            // Construyo la expresión OR solo con las columnas que existan
            $parts = [];
            if ($hasAfipFisica)   { $parts[] = 'COALESCE(doc_afip_constancia_fisica,0)'; }
            if ($hasAfipJuridica) { $parts[] = 'COALESCE(doc_afip_constancia_juridica,0)'; }
            $expr = implode(' OR ', $parts);

            // Si por alguna razón no hay ninguna, no ejecuto
            if ($expr !== '') {
                DB::statement("
                    UPDATE ubicacion_documentos
                    SET doc_afip_constancia = ($expr)
                ");
            }
        }

        if ($hasRecaudacion) {
            DB::statement("
                UPDATE ubicacion_documentos
                SET doc_recaudacion_rn = COALESCE(doc_constancia_recaudacion,0)
            ");
        }
    }

    public function down(): void
    {
        Schema::table('ubicacion_documentos', function (Blueprint $table) {
            if (Schema::hasColumn('ubicacion_documentos', 'doc_afip_constancia')) {
                $table->dropColumn('doc_afip_constancia');
            }
            if (Schema::hasColumn('ubicacion_documentos', 'doc_recaudacion_rn')) {
                $table->dropColumn('doc_recaudacion_rn');
            }
        });
    }
};

