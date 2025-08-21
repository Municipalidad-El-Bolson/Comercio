<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class RubrosTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        // ================================
        // 1) Armar dataset con tus datos
        // ================================
        $dataset = [];

        // Helper para agregar grupos de subrubros
        $add = function (string $mega, string $madre, array $items) use (&$dataset, $now) {
            $mega  = trim($mega);
            $madre = trim($madre);

            foreach ($items as $it) {
                $sub = trim($it);
                if ($sub === '') { continue; }

                $dataset[] = [
                    'mega_rubro'  => $mega,
                    'rubro_madre' => $madre,
                    'subrubro'    => $sub,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ];
            }
        };

        // ================================
        // ====== TUS DEFINICIONES ========
        // (copiadas y organizadas del seeder que pasaste)
        // ================================

        // 1) Comercio y Ventas
        $mega = 'Comercio y Ventas';
        $add($mega, 'Almacenes y Tiendas', [
            'Almacenes naturistas',
            'Supermercados',
            'Despensas',
            'Autoservicios',
        ]);
        $add($mega, 'Artículos para el Hogar', [
            'Artículos de bazar y menaje',
            'Artículos de electricidad e iluminación',
            'Artículos sanitarios',
            'Artículos de riego',
            'Artículos para el hogar',
            'Venta de telas, cortinas, artículos de blanco, mantelería, tapicería',
            'Venta de revestimientos (interior y exterior)',
            'Venta de hierro, chapas, maderas y materiales de construcción en seco',
            'Venta de muebles y colchonerías',
        ]);
        $add($mega, 'Tecnología, Comunicación y Fotografía', [
            'Artículos de comunicación, informática, electrónica y reparación',
            'Venta de aparatos fotográficos, revelados y laboratorios fotográficos',
            'Alquiler de videos y locutorios',
        ]);
        $add($mega, 'Alimentos y Bebidas (comercio minorista/mayorista)', [
            'Venta de comidas preparadas, rotiserías, fábricas de pastas',
            'Venta de repostería casera (tortas, sándwiches, jugos)',
            'Venta y/o fábricas de comestibles regionales (quesos, truchas, etc.)',
            'Venta mayorista de frutas y verduras',
            'Venta de bebidas y recargas de cerveza (growlers)',
        ]);
        $add($mega, 'Rubro Animal y Agro', [
            'Venta de alimentos y accesorios de mascotas (según superficie)',
            'Agroveterinarias',
        ]);
        $add($mega, 'Otros', [
            'Artículos de cuero y artesanías',
            'Casa de artículos deportivos',
            'Librerías y jugueterías',
            'Venta de instrumentos musicales',
            'Venta de máquinas y herramientas de jardín',
            'Venta de neumáticos',
            'Venta de lanas',
            'Venta de leña, carbón y gas envasados',
            'Venta de libros, diarios y revistas',
            'Venta de matafuegos y oxígeno',
        ]);

        // 2) Construcción y Hogar
        $mega = 'Construcción y Hogar';
        $add($mega, 'Ferreterías y Pinturerías', [
            'Ferreterías',
            'Pinturerías',
        ]);
        $add($mega, 'Materiales y Servicios', [
            'Corralones de materiales',
            'Servicios de aserrío móvil',
        ]);

        // 3) Educación y Capacitación
        $mega = 'Educación y Capacitación';
        $add($mega, 'Instituciones y Formación', [
            'Academias e institutos de enseñanza particulares',
            'Jardines de infantes y guarderías',
            'Escuelas de esquí',
            'Establecimientos escolares privados',
        ]);

        // 4) Gastronomía
        $mega = 'Gastronomía';
        $add($mega, 'Gastronomía General', [
            'Restaurantes, parrillas, pizzerías, confiterías sin actividades anexas',
            'Café concerts, pubs, confiterías bailables, snack bar',
            'Cafeterías (hasta 10 mesas / más de 10 mesas)',
            'Fabricación de bebidas con/sin alcohol – fábrica de cerveza con restaurante/pizzería',
            'Food trucks',
            'Pancherías',
            'Fiambrerías',
            'Panaderías y panificados',
            'Pescaderías',
            'Pollerías',
            'Carnicerías',
            'Verdulerías y fruterías',
            'Vinotecas',
            'Regalerías',
        ]);

        // 5) Ocio y Entretenimiento
        $mega = 'Ocio y Entretenimiento';
        $add($mega, 'Entretenimiento', [
            'Videojuegos',
            'Casinos',
            'Televisión por cable y satélite',
            'Peloteros e inflables infantiles',
        ]);

        // 6) Servicios Generales
        $mega = 'Servicios Generales';
        $add($mega, 'Servicios Personales', [
            'Peluquerías',
            'Salones de estética y pedicuras',
            'Terapias alternativas',
        ]);
        $add($mega, 'Servicios Técnicos', [
            'Talleres mecánicos de autos',
            'Talleres de motos y motosierras',
            'Talleres de bicicletas y gomerías',
            'Talleres de chapa y pintura',
            'Talleres de reparación de calzados y costura',
            'Talleres de alineación y balanceo',
        ]);
        $add($mega, 'Servicios Comerciales y Empresariales', [
            'Estudios contables',
            'Estudios jurídicos',
            'Estudios de agrimensura, arquitectura e ingeniería',
            'Gestorías',
            'Inmobiliarias',
            'Casas de crédito y financieras',
            'Administración de tarjetas de crédito y compras',
            'Locales de cobro de servicios (hasta 2 cajas / más de 2 cajas)',
        ]);
        $add($mega, 'Servicios Varios', [
            'Cerrajerías',
            'Lavaderos de automóviles',
            'Soderías',
            'Imprentas y fotocopias',
            'Distribuidores mayoristas de bebidas',
            'Servicio de grúa',
            'Lavanderías y tintorerías',
            'Servicios de seguridad',
            'Servicios fúnebres',
            'Servidores de internet y cable/fibra',
            'Empresas de correo y mensajería',
            'Energías renovables',
        ]);

        // 7) Salud y Bienestar
        $mega = 'Salud y Bienestar';
        $add($mega, 'Salud', [
            'Clínicas y sanatorios médicos en general',
            'Laboratorios de análisis',
            'Farmacias y perfumerías',
            'Geriátricos',
            'Gimnasios',
        ]);

        // 8) Tecnología y Comunicación
        $mega = 'Tecnología y Comunicación';
        $add($mega, 'Comunicaciones', [
            'Telefonía celular',
            'Empresas de radiollamadas y telecomunicaciones',
        ]);

        // 9) Transporte y Turismo
        $mega = 'Transporte y Turismo';
        $add($mega, 'Transporte de Pasajeros', [
            'Transporte urbano de pasajeros',
            'Compañías de media y larga distancia',
            'Transporte escolar',
            'Remises, taxis, taxi-fletes',
            'Agencias de remises',
            'Venta de pasajes larga distancia',
        ]);
        $add($mega, 'Transporte de Cargas', [
            'Transporte de áridos',
            'Transporte de cargas, mudanzas y similares',
            'Transporte de sustancias alimenticias',
        ]);
        $add($mega, 'Servicios y Comercio Automotor', [
            'Alquiler de automotores',
            'Repuestos de automotores',
            'Venta de automotores y motos (nuevos y usados)',
            'Venta de combustibles (estaciones de servicio con o sin anexos)',
            'Lubricentros (cambio de aceites, filtros y servicios básicos de mantenimiento)',
        ]);
        $add($mega, 'Turismo', [
            'Agencias de viajes y turismo',
            'Turismo activo (rafting, parapente, vuelos, mountain bike, trekking, etc.)',
            'Cabalgatas',
            'Campings, balnearios y recreos',
            'Bungalows y cabañas',
            'Hoteles',
            'Hostels, hosterías y moteles',
            'Refugios de montaña',
            'Residenciales',
            'Puestos en feria regional',
            'Venta de pasajes turísticos',
        ]);

        // =======================================
        // 2) Deduplicar por clave compuesta
        // =======================================
        $unique = [];
        $rows = [];
        foreach ($dataset as $r) {
            // Clave case-insensitive para evitar duplicados sutiles
            $key = mb_strtolower($r['mega_rubro'].'|'.$r['rubro_madre'].'|'.$r['subrubro']);
            if (isset($unique[$key])) { continue; }
            $unique[$key] = true;

            // Normalizar espaciado
            $r['mega_rubro']  = preg_replace('/\s+/u', ' ', trim($r['mega_rubro']));
            $r['rubro_madre'] = preg_replace('/\s+/u', ' ', trim($r['rubro_madre']));
            $r['subrubro']    = preg_replace('/\s+/u', ' ', trim($r['subrubro']));

            $rows[] = $r;
        }

        // =======================================
        // 3) UPSERT por chunks (idempotente)
        // =======================================
        foreach (array_chunk($rows, 1000) as $chunk) {
            DB::table('rubros')->upsert(
                $chunk,
                ['mega_rubro', 'rubro_madre', 'subrubro'], // clave única compuesta
                ['updated_at'] // si ya existe, solo actualiza updated_at
            );
        }
    }
}
