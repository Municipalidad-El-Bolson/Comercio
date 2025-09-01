<?php
// app/Services/GeoService.php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class GeoService
{
    /** Rutas a los GeoJSON servidos por la app */
    private string $barriosPath;
    private string $cpuPath;

    /** Estructuras en memoria (FeatureCollection como array) */
    private array $barrios;
    private array $cpu;

    public function __construct(
        string $barriosPath = null,
        string $cpuPath = null
    ) {
        // Por defecto, leer desde /public/geo/...
        $this->barriosPath = $barriosPath ?? public_path('geo/BARRIOS1.json');
        $this->cpuPath     = $cpuPath     ?? public_path('geo/CPU_MEB.json');

        // Cacheamos 24h; invalidás cambiando archivo (puede sumarse hash/mtime)
        $this->barrios = Cache::remember('geo.barrios', 86400, function () {
            return $this->loadGeoJson($this->barriosPath);
        });

        $this->cpu = Cache::remember('geo.cpu', 86400, function () {
            return $this->loadGeoJson($this->cpuPath);
        });
    }

    /**
     * Devuelve lista de nombres de barrio (ordenados, únicos)
     */
    public function barriosList(): array
    {
        $names = array_map(fn($f) => Arr::get($f, 'properties.BARRIO', ''), $this->barrios['features'] ?? []);
        $names = array_values(array_filter(array_unique($names)));
        sort($names, SORT_NATURAL | SORT_FLAG_CASE);
        return $names;
    }

    /**
     * Devuelve lista de CPUs: [['cod'=>'X','nombre'=>'...'], ...] (únicos por cod, orden natural)
     */
    public function cpuList(): array
    {
        $rows = array_map(function ($f) {
            return [
                'cod'    => Arr::get($f, 'properties.CPU_COD', ''),
                'nombre' => Arr::get($f, 'properties.CPU_NOMBRE', ''),
            ];
        }, $this->cpu['features'] ?? []);

        $by = [];
        foreach ($rows as $r) {
            if ($r['cod'] !== '') $by[$r['cod']] = $r;
        }
        $rows = array_values($by);
        usort($rows, fn($a, $b) => strnatcasecmp($a['cod'], $b['cod']));
        return $rows;
    }

    /**
     * Dado un punto (lat, lng), devuelve nombre de barrio o null
     */
    public function matchBarrio(float $lat, float $lng): ?string
    {
        foreach ($this->barrios['features'] ?? [] as $f) {
            $geom = $f['geometry'] ?? null;
            if ($geom && $this->pointInGeometry($lat, $lng, $geom)) {
                return Arr::get($f, 'properties.BARRIO');
            }
        }
        return null;
    }

    /**
     * Dado un punto (lat, lng), devuelve ['cpu_cod'=>..., 'cpu_nombre'=>...] o null
     */
    public function matchCpu(float $lat, float $lng): ?array
    {
        foreach ($this->cpu['features'] ?? [] as $f) {
            $geom = $f['geometry'] ?? null;
            if ($geom && $this->pointInGeometry($lat, $lng, $geom)) {
                return [
                    'cpu_cod'    => Arr::get($f, 'properties.CPU_COD'),
                    'cpu_nombre' => Arr::get($f, 'properties.CPU_NOMBRE'),
                ];
            }
        }
        return null;
    }

    /**
     * Centroide del polígono de una CPU (por código). Devuelve [lat, lng] o null.
     */
    public function centroidByCpu(string $cpuCod): ?array
    {
        foreach ($this->cpu['features'] ?? [] as $f) {
            if (strcasecmp(Arr::get($f, 'properties.CPU_COD', ''), $cpuCod) === 0) {
                $c = $this->geometryCentroid($f['geometry'] ?? null);
                return $c ? [$c['lat'], $c['lng']] : null;
            }
        }
        return null;
    }

    /**
     * Centroide de un barrio por nombre. Devuelve [lat, lng] o null.
     */
    public function centroidByBarrio(string $barrio): ?array
    {
        foreach ($this->barrios['features'] ?? [] as $f) {
            if (strcasecmp(Arr::get($f, 'properties.BARRIO', ''), $barrio) === 0) {
                $c = $this->geometryCentroid($f['geometry'] ?? null);
                return $c ? [$c['lat'], $c['lng']] : null;
            }
        }
        return null;
    }

    // =======================
    // Internals / Helpers
    // =======================

    private function loadGeoJson(string $path): array
    {
        if (!is_file($path)) {
            throw new \RuntimeException("GeoJSON no encontrado: {$path}");
        }
        $json = file_get_contents($path);
        $data = json_decode($json, true);
        if (!is_array($data) || ($data['type'] ?? '') !== 'FeatureCollection') {
            throw new \RuntimeException("GeoJSON inválido en {$path}");
        }
        return $data;
    }

    /**
     * true si (lat,lng) está dentro de la geometría (Polygon o MultiPolygon)
     */
    private function pointInGeometry(float $lat, float $lng, array $geometry): bool
    {
        $type = $geometry['type'] ?? '';
        $coords = $geometry['coordinates'] ?? [];

        if ($type === 'Polygon') {
            // Usamos solo el anillo exterior (coords[0]); si querés excluir huecos, podés restarlos.
            $outer = $coords[0] ?? [];
            return $this->pointInRing($lat, $lng, $outer);
        }

        if ($type === 'MultiPolygon') {
            foreach ($coords as $poly) {
                $outer = $poly[0] ?? [];
                if ($this->pointInRing($lat, $lng, $outer)) {
                    return true;
                }
            }
            return false;
        }

        return false;
    }

    /**
     * Ray casting sobre un ring: ring = [[lng,lat], [lng,lat], ...]
     * x = lng, y = lat
     */
    private function pointInRing(float $lat, float $lng, array $ring): bool
    {
        $inside = false;
        $n = count($ring);
        if ($n < 3) return false;

        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            [$xi, $yi] = [$ring[$i][0], $ring[$i][1]];
            [$xj, $yj] = [$ring[$j][0], $ring[$j][1]];

            // ¿Interseca el semirayo horizontal a la derecha?
            $intersect = (($yi > $lat) !== ($yj > $lat))
                      && ($lng < ($xj - $xi) * ($lat - $yi) / (($yj - $yi) ?: 1e-12) + $xi);

            if ($intersect) $inside = !$inside;
        }
        return $inside;
    }

    /**
     * Centroide aproximado (geométrica) del anillo exterior del polígono (o del primer polígono de un MultiPolygon).
     * Retorna ['lat'=>..., 'lng'=>...] o null.
     */
    private function geometryCentroid(?array $geometry): ?array
    {
        if (!$geometry) return null;
        $type = $geometry['type'] ?? '';
        $coords = $geometry['coordinates'] ?? [];

        // Tomamos el primer anillo exterior disponible
        if ($type === 'Polygon') {
            $ring = $coords[0] ?? [];
        } elseif ($type === 'MultiPolygon') {
            $ring = Arr::get($coords, '0.0', []); // primer polígono, primer anillo
        } else {
            return null;
        }

        $n = count($ring);
        if ($n < 3) return null;

        // Fórmula del centroide de un polígono simple (coordenadas planas; válido para visualización urbana)
        $area2 = 0.0; // 2*área firmada
        $cx = 0.0;
        $cy = 0.0;

        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            $x1 = $ring[$j][0]; $y1 = $ring[$j][1]; // lng,lat
            $x2 = $ring[$i][0]; $y2 = $ring[$i][1];

            $cross = ($x1 * $y2) - ($x2 * $y1);
            $area2 += $cross;
            $cx += ($x1 + $x2) * $cross;
            $cy += ($y1 + $y2) * $cross;
        }

        if (abs($area2) < 1e-12) {
            // Polígono degenerado: fallback al promedio simple
            $xs = array_map(fn($p) => $p[0], $ring);
            $ys = array_map(fn($p) => $p[1], $ring);
            return ['lat' => array_sum($ys) / count($ys), 'lng' => array_sum($xs) / count($xs)];
        }

        $area = $area2 / 2.0;
        $cx = $cx / (3.0 * $area2);
        $cy = $cy / (3.0 * $area2);

        return ['lat' => $cy, 'lng' => $cx];
    }
}
