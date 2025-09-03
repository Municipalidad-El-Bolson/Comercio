<?php
namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class UbicacionGeoEnricher {
  public function __construct(private GeoService $geo) {}

  public function geocode(?string $addr): ?array {
    if (!$addr) return null;

    return Cache::remember('geocode:'.md5($addr), 86400, function() use ($addr) {
      $key = config('services.google.maps_key'); // .env: GOOGLE_MAPS_KEY=...

      // Bounding box para sesgo (aprox El Bolsón)
      // SW: (-42.10, -71.65)  NE: (-41.85, -71.35)
      $bounds = sprintf('%f,%f|%f,%f', -42.10, -71.65, -41.85, -71.35);

      $r = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
        'address'     => $addr,
        'key'         => $key,
        'language'    => 'es-AR',
        'region'      => 'ar',
        'components'  => 'country:AR',
        'bounds'      => $bounds,   // sesgo; no es filtro estricto
      ])->json();

      $res = $r['results'][0] ?? null;
      if (!$res) return null;

      // Filtro estricto a Argentina (y opcional Río Negro)
      $ac = collect($res['address_components'] ?? []);
      $country = $ac->first(fn($c) => in_array('country', $c['types'] ?? []));
      if (($country['short_name'] ?? '') !== 'AR') return null;

      // (Opcional) exigir provincia Río Negro
      $prov = $ac->first(fn($c) => in_array('administrative_area_level_1', $c['types'] ?? []));
      if ($prov && strcasecmp($prov['long_name'] ?? '', 'Río Negro') !== 0) {
        // si querés forzar solo Río Negro, retorná null
        // return null;
      }

      $loc = $res['geometry']['location'] ?? null;
      return $loc ? ['lat' => $loc['lat'], 'lng' => $loc['lng']] : null;
    });
  }


  public function enrich(array $data): array {
    $lat = $data['lat'] ?? null; $lng = $data['lng'] ?? null;

    if ((!$lat || !$lng) && !empty($data['domicilio_comercio'])) {
      if ($p = $this->geocode($data['domicilio_comercio'])) { $lat=$p['lat']; $lng=$p['lng']; }
    }

    if ((!$lat || !$lng) && !empty($data['nomenclatura'])) {
      if ($c = $this->geo->centroidByCpu($data['nomenclatura'])) { $lat=$c[0]; $lng=$c[1]; }
      $data['cpu_cod'] = $data['nomenclatura'];
    }

    if ($lat && $lng) {
      $data['lat'] = $lat; $data['lng'] = $lng;
      $data['barrio'] = $this->geo->matchBarrio($lat,$lng) ?? ($data['barrio'] ?? null);
      if ($cpu = $this->geo->matchCpu($lat,$lng)) {
        $data['cpu_cod'] = $data['cpu_cod'] ?? $cpu['cpu_cod'];
        $data['cpu_nombre'] = $data['cpu_nombre'] ?? $cpu['cpu_nombre'];
      }
    }

    return $data;
  }
}
