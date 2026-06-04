<?php
namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class UbicacionGeoEnricher {
  public function __construct(private GeoService $geo) {}

  private const BBOX = ['minLat'=>-42.10,'maxLat'=>-41.85,'minLng'=>-71.65,'maxLng'=>-71.35];

  private function inBbox(float $lat, float $lng): bool {
    return $lat >= self::BBOX['minLat'] && $lat <= self::BBOX['maxLat']
        && $lng >= self::BBOX['minLng'] && $lng <= self::BBOX['maxLng'];
  }

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
        'key'=>$key,'language'=>'es-AR','region'=>'ar',
        'components'=>'country:AR|administrative_area:Rio Negro|locality:El Bolson',
      ];
      $r = Http::get('https://maps.googleapis.com/maps/api/geocode/json', $baseParams + ['address'=>$addrFull])->json();
      $res = $r['results'][0] ?? null; if (!$res) return null;
      $ac = collect($res['address_components'] ?? []);
      $country = $ac->first(fn($c)=>in_array('country',$c['types'] ?? []));
      if (($country['short_name'] ?? '') !== 'AR') return null;
      $prov = $ac->first(fn($c)=>in_array('administrative_area_level_1',$c['types'] ?? []));
      if ($prov && strcasecmp($prov['long_name'] ?? '','Río Negro') !== 0) return null;
      $loc = $res['geometry']['location'] ?? null; if (!$loc) return null;
      $lat = (float)$loc['lat']; $lng = (float)$loc['lng'];
      if (!$this->inBbox($lat,$lng)) return null;
      return ['lat'=>$lat,'lng'=>$lng];
    });
  }

  public function enrich(array $data): array {
    $lat = $data['lat'] ?? null; 
    $lng = $data['lng'] ?? null;

    // Si no hay coords y hay dirección → geocode
    if ((!$lat || !$lng) && !empty($data['domicilio_comercio'])) {
      if ($p = $this->geocode($data['domicilio_comercio'])) { $lat=$p['lat']; $lng=$p['lng']; }
    }

    // Si vino nomenclatura, usar su centroide como fallback
    if ((!$lat || !$lng) && !empty($data['nomenclatura'])) {
      if ($c = $this->geo->centroidByNomenclatura($data['nomenclatura'])) { $lat=$c[0]; $lng=$c[1]; }
    }

    if ($lat && $lng) {
      $data['lat'] = $lat; 
      $data['lng'] = $lng;

      // Completar barrio y nomenclatura si faltan
      $data['barrio'] = $data['barrio'] ?? $this->geo->matchBarrio($lat,$lng);
      $data['nomenclatura'] = $data['nomenclatura'] ?? $this->geo->matchNomenclatura($lat,$lng);
    }

    return $data;
  }
}
