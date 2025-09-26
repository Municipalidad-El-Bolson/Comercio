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

        // Palabras que no singularizamos aunque terminen en S
        $noSingular = ['ANALISIS','PAIS','BUS','TORAX','POLIRRUBRO','KIOSCO','LUNES','MARTES','MIERCOLES','JUEVES','VIERNES'];

        $singularWord = function(string $w) use ($noSingular, $toUpper): string {
            $u = $toUpper($w);
            if (in_array($u, $noSingular, true)) return $u;
            if (preg_match('/CES$/u', $u)) return preg_replace('/CES$/u', 'Z', $u);               // LUCES -> LUZ
            if (preg_match('/[B-DF-HJ-NP-TV-Z]ES$/u', $u)) return preg_replace('/ES$/u', '', $u); // PAPELES -> PAPEL
            if (preg_match('/[AEIOUÁÉÍÓÚ]S$/u', $u)) return preg_replace('/S$/u', '', $u);        // CASAS -> CASA
            return $u;
        };

        $singularize = fn(string $s) =>
            preg_replace_callback('/\p{L}{3,}/u', fn($m) => $singularWord($m[0]), $s);

        // Palabras-acción: si están presentes, ya “explican” la actividad
        $hasAction = function(string $u): bool {
            return (bool) preg_match('/\b(ALQUILER|VENTA|SERVICIO|SERV\.|ELABORACION|ELAB\.|FABRICACION|FAB\.|PRODUCCION|COLOCACION|MANTENIMIENTO|REPARACION|DISTRIBUIDORA|TALLER|CONSULTOR|CONSULTORIA|CURSO|CLASE|HOSTEL|HOTEL|HOSTERIA|SUPERMERCADO|RESTAURANTE|PANADERIA|PIZZERIA|HELADERIA|KIOSCO|FERRETERIA|VIDRIERIA|PELUQUERIA)\b/u', $u);
        };

        // Listas para clasificar “desnudos”
        $agro = [
            'ARANDANO','CEREZA','FRAMBUESA','MANZANA','NUEZ','NOGAL','PAPA','TRUCHA','VIÑA','VIÑEDO','YERBA',
            'HIGO','PERA','DURAZNO','CEREAL','OLIVO','TAMBO','LECHUGA','TOMATE','AJO','CEBOLLA','ZANAHORIA'
        ];
        $materiales = [
            'TEJA','LADRILLO','CEMENTO','CERAMICO','PORCELANATO','MOSAICO','PUERTA','VENTANA','REVESTIMIENTO',
            'HERRAMIENTA','PINTURA','TABLON','ANDAMIO','PLANCHADA','MEZCLADORA','VIBRADOR','HIERRO','MADERA','LOZA'
        ];

        $classify = function(string $u) use ($agro, $materiales, $stripAccents): ?string {
            // $u ya viene MAYÚSCULAS y singular
            if (preg_match('/\b[ -\/,]/u', $u)) return null; // tiene separadores: ya dice algo
            if (in_array($u, array_map(fn($x)=>$stripAccents($x), array_map('mb_strtoupper',$agro)))) {
                return 'PRODUCCION DE '.$u;
            }
            if (in_array($u, array_map(fn($x)=>$stripAccents($x), array_map('mb_strtoupper',$materiales)))) {
                return 'VENTA DE '.$u;
            }
            return null;
        };

        $normalize = function(string $txt) use ($toUpper, $singularize): string {
            $s = $toUpper(trim($txt));
            $s = preg_replace('/\s+/u', ' ', $s);
            $s = preg_replace('/\bS\/\s*/u', 'SIN ', $s);
            $s = preg_replace('/\bC\/\s*/u', 'CON ', $s);
            $s = $singularize($s);
            // unificar separadores
            $s = preg_replace('/\s*\/\s*/u', ' / ', $s);
            $s = preg_replace('/\s*-\s*/u', ' - ', $s);
            $s = preg_replace('/\s*\,\s*/u', ', ', $s);
            $s = preg_replace('/\s+(\.|\)|,)/u', '$1', $s);
            return trim(preg_replace('/\s+/u', ' ', $s));
        };

        $clarifyIfBare = function(string $name, callable $hasAction, callable $classify): string {
            $u = $name;
            if ($hasAction($u)) return $u;
            $forced = $classify($u);
            return $forced ?? $u;
        };

        $compress = function(string $s) use ($MAX): string {
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

        // ===================== recolectar fuentes =====================
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
                    // Detectar delimitador
                    $delim = (substr_count($first, ';') > substr_count($first, ',')) ? ';' : ',';
                    // Rewind y parsear
                    rewind($fh);
                    $headers = null;
                    while (($row = fgetcsv($fh, 0, $delim)) !== false) {
                        if ($headers === null) {
                            $headers = array_map(fn($h)=>mb_strtoupper(trim((string)$h),'UTF-8'), $row);
                            continue;
                        }
                        $line = array_map('trim', $row);
                        if (!count($line)) continue;

                        // Buscar columna SUBRUBRO (o similar)
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
                    break; // usamos el primer CSV encontrado
                }
            }
        }

        // También recolecto lo que ya exista en la DB (para unificar todo)
        $fromDB = [];
        try {
            if (Schema::hasTable('rubros')) {
                $fromDB = DB::table('rubros')->pluck('subrubro')->filter()->all();
            }
        } catch (\Throwable $e) {
            // si falla, seguimos con CSV/arrays
        }

        // Si querés, podés sumar aquí una lista manual extra:
        $manual = [
                'MERCERIA', 'TIENDA', 'MASAJE', 'SALA DE ELABORACION','KIOSCOS', 'TEJAS', 'FRAMBUESAS', 'VENTA DE HELADOS', 'ELABORACION DE PANIFICADOS',
        ];

        // ===================== normalizar + deduplicar =====================
        $pool = array_merge($fromCSV, $fromDB, $manual);
        $seen = [];
        $final = [];

        foreach ($pool as $raw) {
            $raw = trim((string)$raw);
            if ($raw === '') continue;

            $name = $normalize($raw);                     // MAYÚS + singular + limpieza
            $name = $clarifyIfBare($name, $hasAction, $classify); // VENTA/PRODUCCION si hace falta
            $name = $compress($name);                     // ≤100

            $key = mb_strtolower($name, 'UTF-8');
            if (isset($seen[$key])) continue;
            $seen[$key] = true;

            $final[] = $name;
        }

        // ===================== limpiar tablas (manejo FK) =====================
        try { DB::statement('SET FOREIGN_KEY_CHECKS=0'); } catch (\Throwable $e) {}

        if (Schema::hasTable('ubicacion_rubro')) {
            try { DB::table('ubicacion_rubro')->truncate(); } catch (\Throwable $e) {
                // fallback si TRUNCATE no se puede (por permisos/engine)
                DB::table('ubicacion_rubro')->delete();
            }
        }

        if (Schema::hasTable('rubros')) {
            try { DB::table('rubros')->truncate(); } catch (\Throwable $e) {
                DB::table('rubros')->delete();
            }
        }

        try { DB::statement('SET FOREIGN_KEY_CHECKS=1'); } catch (\Throwable $e) {}

        // ===================== insertar como "un solo rubro" =====================
        $mega  = 'CICI 2025';
        $madre = 'RUBRO UNICO';
        $rows = [];
        foreach ($final as $sub) {
            $rows[] = [
                'mega_rubro'  => $mega,
                'rubro_madre' => $madre,
                'subrubro'    => $sub,
                'created_at'  => $now,
                'updated_at'  => $now,
            ];
        }

        foreach (array_chunk($rows, 1000) as $chunk) {
            DB::table('rubros')->insert($chunk);
        }
    }
}
