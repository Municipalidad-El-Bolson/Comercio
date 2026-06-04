<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UbicacionDocumento extends Model
{
    protected $table = 'ubicacion_documentos';

    protected $fillable = [
        'ubicacion_id',

        // Generales
        'doc_libre_deuda_municipal',
        'doc_planeamiento_urbano',
        'doc_solicitud_habilitacion_pago',
        'doc_afip_constancia',
        'doc_recaudacion_rn',
        'doc_fotocopia_dni',
        'doc_comprobante_uso_local',
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
        'doc_afip_constancia_fisica',
        'doc_afip_constancia_juridica',
        'doc_constancia_recaudacion',
        'doc_acta_constitucion',
        'doc_contrato_societario',
        'doc_docs_representantes',

        // Nuevos/estados
        'doc_manipulacion_alimentos',
        'doc_nota_baja',
        'doc_pago_baja',
        'doc_cert_electricidad',
        'doc_cert_gasista',
        'doc_inf_seg_hig',
        'doc_protocolo_mput',
        'doc_carga_fuego',
        'doc_inf_ascensores',
        'doc_poliza_seguro',
        'doc_cert_cocapri',
        'doc_inf_splif',
        'doc_control_plagas',
        'doc_cert_caldera',
        'doc_cert_zavecom',
        'doc_cert_salud_prov',
        'doc_acta_inspeccion',

        // Uso del inmueble (exclusivos + tipo textual)
        'doc_uso_boleto',
        'doc_uso_contrato',
        'doc_uso_comodato',
        'doc_uso_titulo',
        'doc_uso_cert_ocupacion',
        'doc_uso_inmueble_tipo',
    ];

    protected $casts = [
        // Generales
        'doc_libre_deuda_municipal'        => 'bool',
        'doc_planeamiento_urbano'          => 'bool',
        'doc_solicitud_habilitacion_pago'  => 'bool',
        'doc_afip_constancia'              => 'bool',
        'doc_recaudacion_rn'               => 'bool',
        'doc_fotocopia_dni'                => 'bool',
        'doc_comprobante_uso_local'        => 'bool',
        'doc_comprobante_uso_inmueble'     => 'bool',
        'doc_libre_deuda_tasas_inmueble'   => 'bool',
        'doc_aptitud_tecnica_local'        => 'bool',
        'doc_cocap_rhi'                    => 'bool',
        'doc_nota_carteleria_obras'        => 'bool',
        'doc_libro_actas_100'              => 'bool',
        'doc_final_obra'                   => 'bool',
        'doc_solicitud_cambio_domicilio'   => 'bool',
        'doc_solicitud_cambio_nombre_fantasia' => 'bool',
        'doc_solicitud_cambio_rubro'       => 'bool',
        'doc_solicitud_cambio_baja_rubro'  => 'bool',
        'doc_nota_baja_comercial'          => 'bool',

        // Jurídicas
        'doc_afip_constancia_fisica'       => 'bool',
        'doc_afip_constancia_juridica'     => 'bool',
        'doc_constancia_recaudacion'       => 'bool',
        'doc_acta_constitucion'            => 'bool',
        'doc_contrato_societario'          => 'bool',
        'doc_docs_representantes'          => 'bool',

        // Nuevos/estados
        'doc_manipulacion_alimentos'       => 'bool',
        'doc_nota_baja'                    => 'bool',
        'doc_pago_baja'                    => 'bool',
        'doc_cert_electricidad'            => 'bool',
        'doc_cert_gasista'                 => 'bool',
        'doc_inf_seg_hig'                  => 'bool',
        'doc_protocolo_mput'               => 'bool',
        'doc_carga_fuego'                  => 'bool',
        'doc_inf_ascensores'               => 'bool',
        'doc_poliza_seguro'                => 'bool',
        'doc_cert_cocapri'                 => 'bool',
        'doc_inf_splif'                    => 'bool',
        'doc_control_plagas'               => 'bool',
        'doc_cert_caldera'                 => 'bool',
        'doc_cert_zavecom'                 => 'bool',
        'doc_cert_salud_prov'              => 'bool',
        'doc_acta_inspeccion'              => 'bool',

        // Uso del inmueble
        'doc_uso_boleto'                   => 'bool',
        'doc_uso_contrato'                 => 'bool',
        'doc_uso_comodato'                 => 'bool',
        'doc_uso_titulo'                   => 'bool',
        'doc_uso_cert_ocupacion'           => 'bool',

        // tipo textual queda como string (nullable)
        'doc_uso_inmueble_tipo'            => 'string',
    ];
}


