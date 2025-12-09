<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Carbon;

class RubrosTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $MAX = 100;

        // ===================== utilidades =====================
        $toUpper = fn(string $s) => mb_strtoupper($s, 'UTF-8');

        $stripAccents = function (string $s): string {
            $tr = [
                'Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ñ'=>'N',
                'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n'
            ];
            return strtr($s, $tr);
        };

        $noSingular = ['ANALISIS','PAIS','BUS','TORAX','POLIRRUBRO','KIOSCO','LUNES','MARTES','MIERCOLES','JUEVES','VIERNES'];

        $singularWord = function(string $w) use ($noSingular, $toUpper): string {
            $u = $toUpper($w);
            if (in_array($u, $noSingular, true)) return $u;
            if (preg_match('/CES$/u', $u)) return preg_replace('/CES$/u', 'Z', $u);
            if (preg_match('/[B-DF-HJ-NP-TV-Z]ES$/u', $u)) return preg_replace('/ES$/u', '', $u);
            if (preg_match('/[AEIOUÁÉÍÓÚ]S$/u', $u)) return preg_replace('/S$/u', '', $u);
            return $u;
        };

        $singularize = fn(string $s) =>
            preg_replace_callback('/\p{L}{3,}/u', fn($m) => $singularWord($m[0]), $s);

        $hasAction = function(string $u): bool {
            return (bool) preg_match('/\b(ALQUILER|VENTA|SERVICIO|SERV\.|ELABORACION|ELAB\.|FABRICACION|FAB\.|PRODUCCION|COLOCACION|MANTENIMIENTO|REPARACION|DISTRIBUIDORA|TALLER|CONSULTOR|CONSULTORIA|CURSO|CLASE|HOSTEL|HOTEL|HOSTERIA|SUPERMERCADO|RESTAURANTE|PANADERIA|PIZZERIA|HELADERIA|KIOSCO|FERRETERIA|VIDRIERIA|PELUQUERIA)\b/u', $u);
        };

        $agro = [
            'ARANDANO','CEREZA','FRAMBUESA','MANZANA','NUEZ','NOGAL','PAPA','TRUCHA','VIÑA','VIÑEDO','YERBA',
            'HIGO','PERA','DURAZNO','CEREAL','OLIVO','TAMBO','LECHUGA','TOMATE','AJO','CEBOLLA','ZANAHORIA'
        ];

        $materiales = [
            'TEJA','LADRILLO','CEMENTO','CERAMICO','PORCELANATO','MOSAICO','PUERTA','VENTANA','REVESTIMIENTO',
            'HERRAMIENTA','PINTURA','TABLON','ANDAMIO','PLANCHADA','MEZCLADORA','VIBRADOR','HIERRO','MADERA','LOZA'
        ];

        $classify = function(string $u) use ($agro, $materiales, $stripAccents) {
            if (preg_match('/\b[ -\/,]/u', $u)) return null; 
            if (in_array($u, array_map(fn($x)=>$stripAccents($x), array_map('mb_strtoupper',$agro)))) {
                return 'PRODUCCION DE '.$u;
            }
            if (in_array($u, array_map(fn($x)=>$stripAccents($x), array_map('mb_strtoupper',$materiales)))) {
                return 'VENTA DE '.$u;
            }
            return null;
        };

        $normalize = function(string $txt) use ($toUpper, $singularize) {
            $s = $toUpper(trim($txt));
            $s = preg_replace('/\s+/u', ' ', $s);
            $s = preg_replace('/\bS\/\s*/u', 'SIN ', $s);
            $s = preg_replace('/\bC\/\s*/u', 'CON ', $s);
            $s = $singularize($s);
            $s = preg_replace('/\s*\/\s*/u', ' / ', $s);
            $s = preg_replace('/\s*-\s*/u', ' - ', $s);
            $s = preg_replace('/\s*\,\s*/u', ', ', $s);
            $s = preg_replace('/\s+(\.|\)|,)/u', '$1', $s);
            return trim(preg_replace('/\s+/u', ' ', $s));
        };

        $clarifyIfBare = function(string $name, $hasAction, $classify) {
            $u = $name;
            if ($hasAction($u)) return $u;
            $forced = $classify($u);
            return $forced ?? $u;
        };

        $compress = function(string $s) use ($MAX) {
            if (mb_strlen($s,'UTF-8') <= $MAX) return $s;
            $s = preg_replace('/\b(DEL|DE LA|DE LOS|DE LAS|DE|LA|EL|LOS|LAS|Y)\b/u', '', $s);
            $s = trim(preg_replace('/\s+/u', ' ', $s));
            if (mb_strlen($s,'UTF-8') <= $MAX) return $s;
            $s = preg_replace('/\s*\/\s*/u', '/', $s);
            $s = preg_replace('/\s*-\s*/u', '-', $s);
            $s = trim(preg_replace('/\s+/u', ' ', $s));
            if (mb_strlen($s,'UTF-8') <= $MAX) return $s;
            $cut = mb_substr($s, 0, $MAX, 'UTF-8');
            $space = mb_strrpos($cut, ' ', 0, 'UTF-8');
            if ($space !== false && $space >= 10) $cut = mb_substr($cut, 0, $space, 'UTF-8');
            return rtrim($cut).'…';
        };

        // =======================================================
        //  NUEVO → función para clasificar rubro_general
        // =======================================================
        $generalClassifier = function(string $sub) {
        $u = mb_strtoupper($sub);

        // === 1) ALOJAMIENTO DE ALQUILER TURÍSTICO ===
        if (preg_match('/(HOTEL|HOSTEL|HOSTERIA|HOSPEDA|ALOJAM|CABAÑA|CABAÑAS|APART|APARTAMENT|DEPARTAMENTO|DPTO|CAMPING)/', $u)) {
            return 'ALOJAMIENTO DE ALQUILER TURISTICO';
        }

        // === 2) GASTRONOMÍA ===
        if (preg_match('/(RESTAURANTE|ROTISERIA|PANADERIA|HELADERIA|PIZZERIA|GASTRON|CONFITERIA|CAFETERIA|PARRILLA|CASA DE TE|TE HOUSE|COMIDAS? AL PASO|SANDWICH|FAST FOOD)/', $u)) {
            return 'GASTRONOMIA';
        }

        // === 3) CENTRO DE ESTÉTICA Y SPA ===
        if (preg_match('/(ESTETICA|SPA|MASAJE|KINESIO|DEPILACION|COSMETO|PELUQUERIA|BARBERIA|UÑAS|MANICUR|PEDICUR)/', $u)) {
            return 'CENTRO DE ESTETICA Y SPA';
        }

        // === 4) LAVADEROS DE AUTOS ===
        if (preg_match('/(LAVADERO|LAVA AUTO|LAVADO DE AUTOS|CAR WASH)/', $u)) {
            return 'LAVADEROS DE AUTOS';
        }

        // === 5) LUBRICENTROS ===
        if (preg_match('/(LUBRICENTRO|LUBRICACION|CAMBIO DE ACEITE)/', $u)) {
            return 'LUBRICENTROS';
        }

        // === 6) TALLER AUTOMOTOR ===
        if (preg_match('/(TALLER MECANICO|TALLER|MECANICO|CHAPA Y PINTURA|GOMERIA|ALINEACION|BALANCEO|AMORTIGUACION|ELECTRICIDAD AUTOMOTOR)/', $u)) {
            return 'TALLER DEL AUTOMOTOR';
        }

        // === 7) SALUD ===
        if (preg_match('/(CLINICA|SALUD|HOSPITAL|ENFERMER|MEDIC|KINESIO|FISIOTER|ODONTO|TERAPIA|PSICO|LABORATORIO)/', $u)) {
            return 'SALUD';
        }

        // === 8) GIMNASIOS ===
        if (preg_match('/(GYM|GIMNASIO|CROSSFIT|FITNESS|ENTRENAMIENTO|ZUMBA|PILATES)/', $u)) {
            return 'GIMNASIOS';
        }

        // === 9) ALQUILER DE CANCHAS ===
        if (preg_match('/(CANCHA|PADEL|PÁDEL|TENIS|FUTBOL|VOLEY|BASQUET|ALQUILER DE CANCHA)/', $u)) {
            return 'ALQUILER DE CANCHAS';
        }

        // === 10) VENTA DE ARTESANÍAS Y PRODUCTOS REGIONALES ===
        if (preg_match('/(ARTESANIA|ARTESANIAS|REGIONAL|PRODUCTOS REGIONAL|SOUVENIR|ARTESANAL)/', $u)) {
            return 'VENTA DE ARTESANIAS Y PRODUCTOS REGIONALES';
        }

        // === 11) SALA DE ELABORACIÓN ===
        if (preg_match('/(SALA DE ELABORACION|ELABORACION|FÁBRICA|PRODUCCION DE ALIMENT|PRODUCCION ARTESANAL)/', $u)) {
            return 'SALA DE ELABORACION';
        }

        // === 12) COCINAS DOMICILIARIAS — PERMISO HABILITANTE ===
        if (preg_match('/(COCINA DOMICILIARIA|COCINA EN CASA|COCINA CASERA|COCINA FAMILIAR|ELABORACION DOMICILIARIA)/', $u)) {
            return 'COCINA DOMICILIARIA';
        }

        // === 13) SERVICIOS GENERALES ===
        if (preg_match('/(SERVICIO|CONSULTOR|MANTENIM|ASESOR|LAVANDERIA|LAVARROPA|REPARACION|INSTALACION)/', $u)) {
            return 'SERVICIOS';
        }

        // === 14) COMERCIO GENERAL ===
        if (preg_match('/(VENTA|KIOSCO|QUIOSCO|TIENDA|INDUMENTARIA|LIMPIEZA|COMERCIO|ARTICULOS|BAZAR|REGALOS)/', $u)) {
            return 'COMERCIO';
        }

        // === 15) AGRO / PRODUCCIÓN ===
        if (preg_match('/(PRODUCCION|ELABORACION|AGRIC|FRUTIC|ARANDANO|FRAMBUESA|CERVEZA ARTESANAL)/', $u)) {
            return 'AGRO / PRODUCCION';
        }

        // === Otros ===
        return 'OTROS';
    };

        // =======================================================

        // ===================== Recolectar fuentes =====================
        $candidatos = [
            database_path('data/Nomenclador_de_Actividades_CICI_2025.csv'),
            database_path('data/Nomenclador_de_Actividades_CICI_2025 (1).csv'),
            database_path('Nomenclador_de_Actividades_CICI_2025.csv'),
            base_path('Nomenclador_de_Actividades_CICI_2025.csv'),
            storage_path('app/Nomenclador_de_Actividades_CICI_2025.csv'),
        ];

        $fromCSV = [];
        foreach ($candidatos as $ruta) {
            if (is_readable($ruta)) {
                $fh = fopen($ruta, 'r');
                if ($fh !== false) {
                    $first = fgets($fh);
                    if ($first === false) { fclose($fh); continue; }

                    $delim = (substr_count($first, ';') > substr_count($first, ',')) ? ';' : ',';

                    rewind($fh);
                    $headers = null;
                    while (($row = fgetcsv($fh, 0, $delim)) !== false) {
                        if ($headers === null) {
                            $headers = array_map(fn($h)=>mb_strtoupper(trim((string)$h),'UTF-8'), $row);
                            continue;
                        }
                        $line = array_map('trim', $row);
                        if (!count($line)) continue;

                        $idx = null;
                        foreach ($headers as $i => $h) {
                            if (preg_match('/SUB.*RUBRO/u', $h)) { $idx = $i; break; }
                            if (preg_match('/NOMBRE|DESCRIP/u', $h)) { $idx = $idx ?? $i; }
                        }
                        if ($idx === null || !isset($line[$idx])) continue;

                        $val = (string)$line[$idx];
                        if ($val !== '') $fromCSV[] = $val;
                    }
                    fclose($fh);
                    break;
                }
            }
        }

        $fromDB = [];
        try {
            if (Schema::hasTable('rubros')) {
                $fromDB = DB::table('rubros')->pluck('subrubro')->filter()->all();
            }
        } catch (\Throwable) {}

        $manual = [
            'MERCERIA',
            'TIENDA',
            'MASAJE',
            'SALA DE ELABORACION',
            'KIOSCOS',
            'TEJAS',
            'FRAMBUESAS',
            'VENTA DE HELADOS',
            'ELABORACION DE PANIFICADOS',
            'VIDIRIERIA',
            'COTILLONERIA',
            'PAPELERIA',
            'EDERSA',
            'HOTEL',
            'HOSTERIA',
            'HOSTEL',
            'HOSPEDAJE',
            'ALOJAMIENTO TURÍSTICO',
            'CABAÑAS',
            'APARTAMENTOS TURÍSTICOS',
            'DEPARTAMENTOS TURÍSTICOS',
            'CAMPING',
            'OFICINA DE INFORMES Y RECAUDACIONES',
            'HORMIGON (VENTA)',
            'PANTALLA LED CON PUBLICIDAD',
            'ARTICULOS DE LIMPIEZA (VENTA)',
            'VENTA DE CELULARES, ACCESORIOS Y REPARACIÓN',
            'OFICINA DE OBRA SOCIAL',
            'VIDRIERÍA',
            'DEPÓSITO',
            'VENTA DE MERCADERÍA POR MAYOR Y MENOR',
            'COTILLÓN',
            'PAPELERA',
            'OFICINA DE INFORMES Y RECAUDACIONES',
            'VENTA DE HORMIGÓN',
            'PANTALLA LED CON PUBLICIDAD',
            'VENTA DE ARTÍCULOS DE LIMPIEZA',
            'ÓPTICA',
            'VENTA DE ARTESANÍAS Y PRODUCTOS REGIONALES',
            'EXCURSIONES VEHÍCULOS CON ORUGA',
            'DEPOSITO',
            'VENTA MAYORISTA',
            'VENTA DE ARTESANÍAS Y PRODUCTOS REGIONALES',
            'BODEGA CON DEGUSTACIÓN',
            'DEPÓSITO Y VENTA DE MERCADERÍAS',
            'DISTRIBUIDORA / ES',
            'VETERINARIA',
            'TAROT',
            'COOPERATIVA DE TRABAJO',
            'LENCERÍA',
            'VENTA DE INDUMENTARIA',
            'VENTA DE SANITARIOS',
            'ESCUELA COREOGRAFÍA',
            'OFICINA DE OBRA SOCIAL',
            'DEPÓSITO DE CARGAS',
            'VENTA DE HERRAMIENTAS ELÉCTRICAS',
            'ESTABLECIMIENTO AGRÍCOLA',
            'ELABORACIÓN DE DULCES',
            'ELABORACIÓN DE JUGOS NATURALES',
            'CONSERVAS',
            'SALA DE CONSUMO Y DEGUSTACIÓN',
            'ELABORACIÓN Y VENTA DE CHOCOLATES ARTESANALES',
            'SECADERO DE FRUTAS',
            'CONFITURAS',
            'MARMOLERÍA',
            'VENTA DE BACHAS PARA COCINAS',
            'VENTA DE INODOROS',
            'VENTA DE CARBÓN Y LEÑA',
            'VENTA DE LEÑA EN BOLSA',
            'SALA DE FRACCIONAMIENTO DE MIEL',
        ];


        // ===================== normalizar + deduplicar =====================
        $pool = array_merge($fromCSV, $fromDB, $manual);
        $seen = [];
        $final = [];

        foreach ($pool as $raw) {
            $raw = trim((string)$raw);
            if ($raw === '') continue;

            $name = $normalize($raw);
            $name = $clarifyIfBare($name, $hasAction, $classify);
            $name = $compress($name);


            $key = mb_strtolower($stripAccents($name), 'UTF-8');

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $final[] = $name;   // 👈 acá mantenés la versión linda, con acentos
        }


        // ===================== limpiar tablas =====================
        try { DB::statement('SET FOREIGN_KEY_CHECKS=0'); } catch (\Throwable $e) {}

        if (Schema::hasTable('ubicacion_rubro')) {
            try { DB::table('ubicacion_rubro')->truncate(); } catch (\Throwable $e) {
                DB::table('ubicacion_rubro')->delete();
            }
        }

        if (Schema::hasTable('rubros')) {
            try { DB::table('rubros')->truncate(); } catch (\Throwable $e) {
                DB::table('rubros')->delete();
            }
        }

        try { DB::statement('SET FOREIGN_KEY_CHECKS=1'); } catch (\Throwable $e) {}

        // ===================== insertar =====================
        $mega  = 'CICI 2025';
        $madre = 'RUBRO UNICO';
        $rows = [];
        foreach ($final as $sub) {
            $rows[] = [
                'mega_rubro'     => $mega,
                'rubro_madre'    => $madre,
                'subrubro'       => $sub,
                'rubro_general'  => $generalClassifier($sub),   // <<< NUEVO
                'created_at'     => $now,
                'updated_at'     => $now,
            ];
        }

        foreach (array_chunk($rows, 1000) as $chunk) {
            DB::table('rubros')->insert($chunk);
        }
    }
}
