<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class GeoService
{
    private string $barriosPath;
    private string $cpuPath;
    private string $catastroPath;

    private array $barrios;
    private array $cpu;
    private array $catastro;

    public function __construct(string $barriosPath = null, string $cpuPath = null, string $catastroPath = null)
    {
        $this->barriosPath  = $barriosPath  ?? public_path('geo/BARRIOS1.json');
        $this->cpuPath      = $cpuPath      ?? public_path('geo/CPU_MEB.json'); // legacy
        $this->catastroPath = $catastroPath ?? public_path('geo/CATASTRO_GEO.json');

        $this->barrios = Cache::remember('geo.barrios', 86400, fn() => $this->loadGeoJson($this->barriosPath));
        $this->cpu     = Cache::remember('geo.cpu', 86400, fn() => is_file($this->cpuPath) ? $this->loadGeoJson($this->cpuPath) : ['type'=>'FeatureCollection','features'=>[]]);
        $this->catastro= Cache::remember('geo.catastro', 86400, fn() => $this->loadGeoJson($this->catastroPath));
    }

    public function barriosList(): array
    {
        $names = array_map(fn($f) => Arr::get($f, 'properties.BARRIO', ''), $this->barrios['features'] ?? []);
        $names = array_values(array_filter(array_unique($names)));
        sort($names, SORT_NATURAL | SORT_FLAG_CASE);
        return $names;
    }

    /** Lista de nomenclaturas (catastro) */
    public function catastroList(): array
    {
        $key = $this->catastroNomenKey();
        $vals = array_map(fn($f) => Arr::get($f, "properties.$key", ''), $this->catastro['features'] ?? []);
        $vals = array_values(array_filter(array_unique($vals)));
        sort($vals, SORT_NATURAL | SORT_FLAG_CASE);
        return $vals;
    }

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

    /** Nomenclatura por punto (devuelve string o null) */
    public function matchNomenclatura(float $lat, float $lng): ?string
    {
        $key = $this->catastroNomenKey();
        foreach ($this->catastro['features'] ?? [] as $f) {
            $geom = $f['geometry'] ?? null;
            if ($geom && $this->pointInGeometry($lat, $lng, $geom)) {
                return Arr::get($f, "properties.$key");
            }
        }
        return null;
    }

    /** Centroide por nomenclatura (para ubicar/zoom) */
    public function centroidByNomenclatura(string $nomen): ?array
    {
        $key = $this->catastroNomenKey();
        foreach ($this->catastro['features'] ?? [] as $f) {
            if (strcasecmp(Arr::get($f, "properties.$key", ''), $nomen) === 0) {
                $c = $this->geometryCentroid($f['geometry'] ?? null);
                return $c ? [$c['lat'], $c['lng']] : null;
            }
        }
        return null;
    }

    /** LEGACY: CPUs (se deja por compatibilidad) */
    public function cpuList(): array
    {
        $rows = array_map(function ($f) {
            return [
                'cod'    => Arr::get($f, 'properties.CPU_COD', ''),
                'nombre' => Arr::get($f, 'properties.CPU_NOMBRE', ''),
            ];
        }, $this->cpu['features'] ?? []);

        $by = [];
        foreach ($rows as $r) if ($r['cod'] !== '') $by[$r['cod']] = $r;
        $rows = array_values($by);
        usort($rows, fn($a, $b) => strnatcasecmp($a['cod'], $b['cod']));
        return $rows;
    }

    // ========= Internos =========

    private function loadGeoJson(string $path): array
    {
        if (!is_file($path)) throw new \RuntimeException("GeoJSON no encontrado: {$path}");
        $json = file_get_contents($path);
        $data = json_decode($json, true);
        if (!is_array($data) || ($data['type'] ?? '') !== 'FeatureCollection') {
            throw new \RuntimeException("GeoJSON inválido en {$path}");
        }
        return $data;
    }

    /** Detecta la key que guarda la nomenclatura en Catastro */
    private function catastroNomenKey(): string
    {
        $features = $this->catastro['features'] ?? [];
        $props = isset($features[0]['properties']) && is_array($features[0]['properties']) ? array_keys($features[0]['properties']) : [];
        // candidatos comunes
        $candidates = ['NOMEN','NOMENC','NOMENCLATURA','nomenclatura','RefName','refname'];
        foreach ($candidates as $k) if (in_array($k, $props, true)) return $k;
        // fallback: primera key que contenga "nomen"
        foreach ($props as $k) if (stripos($k, 'nomen') !== false) return $k;
        // última opción
        return $props[0] ?? 'NOMEN';
    }

    private function pointInGeometry(float $lat, float $lng, array $geometry): bool
    {
        $type = $geometry['type'] ?? '';
        $coords = $geometry['coordinates'] ?? [];

        if ($type === 'Polygon') {
            $outer = $coords[0] ?? [];
            return $this->pointInRing($lat, $lng, $outer);
        }
        if ($type === 'MultiPolygon') {
            foreach ($coords as $poly) {
                $outer = $poly[0] ?? [];
                if ($this->pointInRing($lat, $lng, $outer)) return true;
            }
            return false;
        }
        return false;
    }

    private function pointInRing(float $lat, float $lng, array $ring): bool
    {
        $inside = false; $n = count($ring); if ($n < 3) return false;
        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            [$xi, $yi] = [$ring[$i][0], $ring[$i][1]];
            [$xj, $yj] = [$ring[$j][0], $ring[$j][1]];
            $intersect = (($yi > $lat) !== ($yj > $lat))
                      && ($lng < ($xj - $xi) * ($lat - $yi) / (($yj - $yi) ?: 1e-12) + $xi);
            if ($intersect) $inside = !$inside;
        }
        return $inside;
    }

    private function geometryCentroid(?array $geometry): ?array
    {
        if (!$geometry) return null;
        $type = $geometry['type'] ?? '';
        $coords = $geometry['coordinates'] ?? [];
        $ring = $type === 'Polygon' ? ($coords[0] ?? []) : ($type === 'MultiPolygon' ? Arr::get($coords, '0.0', []) : []);
        $n = count($ring); if ($n < 3) return null;

        $area2 = 0.0; $cx = 0.0; $cy = 0.0;
        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            $x1 = $ring[$j][0]; $y1 = $ring[$j][1];
            $x2 = $ring[$i][0]; $y2 = $ring[$i][1];
            $cross = ($x1 * $y2) - ($x2 * $y1);
            $area2 += $cross; $cx += ($x1 + $x2) * $cross; $cy += ($y1 + $y2) * $cross;
        }
        if (abs($area2) < 1e-12) {
            $xs = array_map(fn($p)=>$p[0], $ring); $ys = array_map(fn($p)=>$p[1], $ring);
            return ['lat'=>array_sum($ys)/count($ys), 'lng'=>array_sum($xs)/count($xs)];
        }
        $cx = $cx / (3.0 * $area2); $cy = $cy / (3.0 * $area2);
        return ['lat'=>$cy, 'lng'=>$cx];
    }
}
