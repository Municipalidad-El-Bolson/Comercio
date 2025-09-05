<?php
namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class UbicacionGeoEnricher {
  public function __construct(private GeoService $geo) {}

  /** BBOX de El Bolsón (aprox) */
  private const BBOX = [
    'minLat' => -42.10, 'maxLat' => -41.85,
    'minLng' => -71.65, 'maxLng' => -71.35,
  ];

  /** ¿Está (lat,lng) dentro del bbox? */
  private function inBbox(float $lat, float $lng): bool {
    return $lat >= self::BBOX['minLat'] && $lat <= self::BBOX['maxLat']
        && $lng >= self::BBOX['minLng'] && $lng <= self::BBOX['maxLng'];
  }

  /** Asegura contexto local en la address */
  private function ensureLocalContext(string $addr): string {
    $txt = trim($addr);
    $hasBolson   = stripos($txt, 'Bolsón') !== false || stripos($txt, 'Bolson') !== false;
    $hasRioNegro = stripos($txt, 'Río Negro') !== false || stripos($txt, 'Rio Negro') !== false;
    $hasAR       = stripos($txt, 'Argentina') !== false;
    if (!$hasBolson)   $txt .= ', El Bolsón';
    if (!$hasRioNegro) $txt .= ', Río Negro';
    if (!$hasAR)       $txt .= ', Argentina';
    return $txt;
  }

  public function geocode(?string $addr): ?array {
    if (!$addr) return null;

    $addrFull = $this->ensureLocalContext($addr);
    return Cache::remember('geocode:'.md5($addrFull), 86400, function() use ($addrFull) {
      $key = config('services.google.maps_key');

      $baseParams = [
        'key'        => $key,
        'language'   => 'es-AR',
        'region'     => 'ar',
        'components' => 'country:AR|administrative_area:Rio Negro|locality:El Bolson',
      ];

      // Intento principal
      $r = Http::get('https://maps.googleapis.com/maps/api/geocode/json',
            $baseParams + ['address' => $addrFull])->json();
      $res = $r['results'][0] ?? null;
      if (!$res) return null;

      // Chequear país/provincia
      $ac = collect($res['address_components'] ?? []);
      $country = $ac->first(fn($c) => in_array('country', $c['types'] ?? []));
      if (($country['short_name'] ?? '') !== 'AR') return null;

      $prov = $ac->first(fn($c) => in_array('administrative_area_level_1', $c['types'] ?? []));
      if ($prov && strcasecmp($prov['long_name'] ?? '', 'Río Negro') !== 0) return null;

      $loc = $res['geometry']['location'] ?? null;
      if (!$loc) return null;

      $lat = (float)$loc['lat']; 
      $lng = (float)$loc['lng'];
      if (!$this->inBbox($lat, $lng)) {
        return null; // fuera de El Bolsón
      }
      return ['lat' => $lat, 'lng' => $lng];
    });
  }

  public function enrich(array $data): array {
    $lat = $data['lat'] ?? null; 
    $lng = $data['lng'] ?? null;

    if ((!$lat || !$lng) && !empty($data['domicilio_comercio'])) {
      if ($p = $this->geocode($data['domicilio_comercio'])) { 
        $lat=$p['lat']; 
        $lng=$p['lng']; 
      }
    }

    if ((!$lat || !$lng) && !empty($data['nomenclatura'])) {
      if ($c = $this->geo->centroidByCpu($data['nomenclatura'])) { 
        $lat=$c[0]; 
        $lng=$c[1]; 
      }
      $data['cpu_cod'] = $data['nomenclatura'];
    }

    if ($lat && $lng) {
      $data['lat'] = $lat; 
      $data['lng'] = $lng;
      $data['barrio'] = $this->geo->matchBarrio($lat,$lng) ?? ($data['barrio'] ?? null);
      if ($cpu = $this->geo->matchCpu($lat,$lng)) {
        $data['cpu_cod'] = $data['cpu_cod'] ?? $cpu['cpu_cod'];
        $data['cpu_nombre'] = $data['cpu_nombre'] ?? $cpu['cpu_nombre'];
      }
    }

    return $data;
  }
}

