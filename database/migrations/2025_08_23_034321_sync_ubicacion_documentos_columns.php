<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $boolCols = [
        // Generales (canónicas)
        'doc_libre_deuda_municipal',
        'doc_planeamiento_urbano',
        'doc_solicitud_habilitacion_pago',
        'doc_comprobante_uso_local',
        'doc_afip_constancia',            // canónica nueva
        'doc_recaudacion_rn',             // canónica nueva
        'doc_fotocopia_dni',
        'doc_comprobante_uso_inmueble',
        'doc_libre_deuda_tasas_inmueble',
        'doc_aptitud_tecnica_local',
        'doc_cocap_rhi',
        'doc_nota_carteleria_obras',
        'doc_libro_actas_100',
        'doc_final_obra',
        'doc_solicitud_cambio_domicilio',
        'doc_solicitud_cambio_nombre_fantasia',
        'doc_solicitud_cambio_rubro',
        'doc_solicitud_cambio_baja_rubro',
        'doc_nota_baja_comercial',

        // Jurídicas
        'doc_acta_constitucion',
        'doc_contrato_societario',
        'doc_docs_representantes',

        // Legacy que todavía pueden aparecer en inserciones hasta terminar la transición
        'doc_afip_constancia_fisica',
        'doc_afip_constancia_juridica',
        'doc_constancia_recaudacion',
    ];

    public function up(): void
    {
        Schema::table('ubicacion_documentos', function (Blueprint $table) {
            foreach ($this->boolCols as $col) {
                if (!Schema::hasColumn('ubicacion_documentos', $col)) {
                    $table->boolean($col)->default(false);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('ubicacion_documentos', function (Blueprint $table) {
            foreach (['doc_afip_constancia', 'doc_recaudacion_rn'] as $col) {
                if (Schema::hasColumn('ubicacion_documentos', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
