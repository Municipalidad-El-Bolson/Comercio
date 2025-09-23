<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ubicacion_documentos', function (Blueprint $table) {
            // Entrámite extra
            if (!Schema::hasColumn('ubicacion_documentos','doc_manipulacion_alimentos')) {
                $table->boolean('doc_manipulacion_alimentos')->default(false);
            }

            // Baja
            if (!Schema::hasColumn('ubicacion_documentos','doc_nota_baja')) {
                $table->boolean('doc_nota_baja')->default(false);
            }
            if (!Schema::hasColumn('ubicacion_documentos','doc_pago_baja')) {
                $table->boolean('doc_pago_baja')->default(false);
            }

            // Irregular
            foreach ([
                'doc_cert_electricidad','doc_cert_gasista','doc_inf_seg_hig',
                'doc_protocolo_mput','doc_carga_fuego','doc_inf_ascensores',
                'doc_poliza_seguro','doc_cert_cocapri','doc_inf_splif',
                'doc_control_plagas','doc_cert_caldera','doc_cert_zavecom',
                'doc_cert_salud_prov',
                // Uso de inmueble (flags)
                'doc_uso_boleto','doc_uso_contrato','doc_uso_comodato',
                'doc_uso_titulo','doc_uso_cert_ocupacion',
            ] as $col) {
                if (!Schema::hasColumn('ubicacion_documentos', $col)) {
                    $table->boolean($col)->default(false);
                }
            }

            // Uso de inmueble (tipo textual)
            if (!Schema::hasColumn('ubicacion_documentos','doc_uso_inmueble_tipo')) {
                $table->string('doc_uso_inmueble_tipo', 30)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('ubicacion_documentos', function (Blueprint $table) {
            $cols = [
                'doc_manipulacion_alimentos',
                'doc_nota_baja','doc_pago_baja',
                'doc_cert_electricidad','doc_cert_gasista','doc_inf_seg_hig',
                'doc_protocolo_mput','doc_carga_fuego','doc_inf_ascensores',
                'doc_poliza_seguro','doc_cert_cocapri','doc_inf_splif',
                'doc_control_plagas','doc_cert_caldera','doc_cert_zavecom',
                'doc_cert_salud_prov',
                'doc_uso_boleto','doc_uso_contrato','doc_uso_comodato',
                'doc_uso_titulo','doc_uso_cert_ocupacion',
                'doc_uso_inmueble_tipo',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('ubicacion_documentos', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
