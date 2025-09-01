<?php
namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class UbicacionGeoEnricher {
  public function __construct(private GeoService $geo) {}

  public function geocode(?string $addr): ?array {
    if (!$addr) return null;
    return Cache::remember('geocode:'.md5($addr), 86400, function() use ($addr) {
      $key = config('services.google.maps_key'); // ponelo en .env
      $r = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
        'address'=>$addr,'key'=>$key,
      ])->json();
      $loc = $r['results'][0]['geometry']['location'] ?? null;
      return $loc ? ['lat'=>$loc['lat'],'lng'=>$loc['lng']] : null;
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
