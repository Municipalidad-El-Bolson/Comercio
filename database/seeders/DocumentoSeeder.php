<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Documento;

class DocumentoSeeder extends Seeder
{
    public function run(): void
    {
        $nombres = [
            // --- Notas ---
            'Nota de Descuento de tasas 50%',
            'Nota de Suspensión',
            'Nota de Baja Consensual de tasas',
            'Nota de solicitud de HC',
            'Nota de Permiso para Plaza Cultural',
            'Nota de Permiso para espectáculos en vivo',
            'Nota de Permiso para espectáculos en espacios cerrados',
            'Nota de prórroga',

            // --- Documentación ---
            'Informe de Seguridad e Higiene',
            'Informe de electricidad',
            'Informe técnico gasista matriculado',
            'Informe puesta a tierra y continuidad de tasas',
            'Informe SPLIF',
            'Informe medio ambiente',
            'Póliza de seguro',
            'Libre deuda Personal/Titular',
            'Libre deuda Inmueble',
            'Final de obra',
            'Certificado Turismo RN',
            'Constancia de Turismo RN',
            'DNI',
            'ARCA',
            'IIBB',
            'Título de propiedad',
            'Boleto de compraventa',
            'Contrato de Locación',
            'Contrato de Comodato',
            'Certificado de ocupación',
            'CO.CA.PRHI',
            'Certificado de salud RN',
            'Certificado de habilitación de RN',
            'Zavecom',
            'Informe tratamientos de residuos patológicos',
        ];

        foreach ($nombres as $nombre) {
            Documento::updateOrCreate(
                ['nombre' => $nombre],
                ['activo' => true]
            );
        }
    }
}
