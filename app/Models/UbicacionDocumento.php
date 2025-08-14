<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UbicacionDocumento extends Model
{
    protected $table = 'ubicacion_documentos';
    protected $fillable = [
        'ubicacion_id',
        'doc_libre_deuda_municipal','doc_planeamiento_urbano','doc_solicitud_habilitacion_pago',
        'doc_afip_constancia_fisica','doc_fotocopia_dni','doc_constancia_recaudacion',
        'doc_afip_constancia_juridica','doc_acta_constitucion','doc_contrato_societario','doc_docs_representantes',
        'doc_comprobante_uso_local',
    ];
}
