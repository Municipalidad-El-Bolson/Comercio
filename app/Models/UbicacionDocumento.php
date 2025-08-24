<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UbicacionDocumento extends Model
{
    protected $table = 'ubicacion_documentos';
    protected $fillable = [
        'ubicacion_id',
        'doc_libre_deuda_municipal',
        'doc_planeamiento_urbano',
        'doc_solicitud_habilitacion_pago',
        'doc_comprobante_uso_local',
        'doc_afip_constancia', // clave "canónica"
        'doc_afip_constancia_fisica', // alias posibles
        'doc_afip_constancia_juridica', // alias posibles
        'doc_recaudacion_rn', // clave "canónica"
        'doc_constancia_recaudacion', // alias posible
        'doc_fotocopia_dni',
        'doc_comprobante_uso_inmueble',
        'doc_libre_deuda_tasas_inmueble',
        'doc_aptitud_tecnica_local',
        'doc_cocap_rhi',
        'doc_nota_carteleria_obras',
        'doc_libro_actas_100',
        // Jurídicas
        'doc_acta_constitucion',
        'doc_contrato_societario',
        'doc_docs_representantes',
    ];


    protected $casts = [
        'doc_libre_deuda_municipal' => 'bool',
        'doc_planeamiento_urbano' => 'bool',
        'doc_solicitud_habilitacion_pago' => 'bool',
        'doc_comprobante_uso_local' => 'bool',
        'doc_afip_constancia' => 'bool',
        'doc_afip_constancia_fisica' => 'bool',
        'doc_afip_constancia_juridica' => 'bool',
        'doc_recaudacion_rn' => 'bool',
        'doc_constancia_recaudacion' => 'bool',
        'doc_fotocopia_dni' => 'bool',
        'doc_comprobante_uso_inmueble' => 'bool',
        'doc_libre_deuda_tasas_inmueble' => 'bool',
        'doc_aptitud_tecnica_local' => 'bool',
        'doc_cocap_rhi' => 'bool',
        'doc_nota_carteleria_obras' => 'bool',
        'doc_libro_actas_100' => 'bool',
        'doc_acta_constitucion' => 'bool',
        'doc_contrato_societario' => 'bool',
        'doc_docs_representantes' => 'bool',
    ];


    
}
