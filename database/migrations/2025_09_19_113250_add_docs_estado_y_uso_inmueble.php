<?php

// database/migrations/2025_09_18_000001_add_docs_estado_y_uso_inmueble.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::table('ubicacion_documentos', function (Blueprint $t) {
      // Entrámite: nuevo
      $t->boolean('doc_manipulacion_alimentos')->default(false);

      // Baja
      $t->boolean('doc_nota_baja')->default(false);
      $t->boolean('doc_pago_baja')->default(false);

      // Irregular (032)
      $t->boolean('doc_cert_electricidad')->default(false);
      $t->boolean('doc_cert_gasista')->default(false);
      $t->boolean('doc_inf_seg_hig')->default(false);
      $t->boolean('doc_protocolo_mput')->default(false); // medición puesta a tierra
      $t->boolean('doc_carga_fuego')->default(false);
      $t->boolean('doc_inf_ascensores')->default(false);
      $t->boolean('doc_poliza_seguro')->default(false);
      $t->boolean('doc_cert_cocapri')->default(false);
      $t->boolean('doc_inf_splif')->default(false);
      $t->boolean('doc_control_plagas')->default(false);
      $t->boolean('doc_cert_caldera')->default(false);
      $t->boolean('doc_cert_zavecom')->default(false);
      $t->boolean('doc_cert_salud_prov')->default(false);

      // Uso de inmueble (selector + booleans 0/1 como pediste)
      $t->boolean('doc_uso_boleto')->default(false);
      $t->boolean('doc_uso_contrato')->default(false);
      $t->boolean('doc_uso_comodato')->default(false);
      $t->boolean('doc_uso_titulo')->default(false);
      $t->boolean('doc_uso_cert_ocupacion')->default(false);
      // opcional: guardar el valor elegido textual
      $t->string('doc_uso_inmueble_tipo')->nullable();
    });
  }

  public function down(): void {
    Schema::table('ubicacion_documentos', function (Blueprint $t) {
      $cols = [
        'doc_manipulacion_alimentos','doc_nota_baja','doc_pago_baja',
        'doc_cert_electricidad','doc_cert_gasista','doc_inf_seg_hig','doc_protocolo_mput',
        'doc_carga_fuego','doc_inf_ascensores','doc_poliza_seguro','doc_cert_cocapri',
        'doc_inf_splif','doc_control_plagas','doc_cert_caldera','doc_cert_zavecom','doc_cert_salud_prov',
        'doc_uso_boleto','doc_uso_contrato','doc_uso_comodato','doc_uso_titulo','doc_uso_cert_ocupacion',
        'doc_uso_inmueble_tipo',
      ];
      foreach ($cols as $c) { if (Schema::hasColumn('ubicacion_documentos',$c)) $t->dropColumn($c); }
    });
  }
};
