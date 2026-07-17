@php
  $items = collect($chartItems ?? []);
  $total = max(0, (int) ($chartTotal ?? $items->sum('cantidad')));
  $colors = ['#286da8','#17845f','#e5a528','#d95b5b','#7656a8','#2b9aa0','#d87936','#587b9b','#a55275','#6f9b45','#4d62bd','#ba704b','#3d8b73','#9b6c35','#687785','#c04f82'];
  $cx = 150; $cy = 150; $radius = 112; $start = -90;
  $slices = [];
  foreach ($items as $index => $item) {
      $value = (int) data_get($item, 'cantidad', 0);
      if ($value <= 0 || $total <= 0) continue;
      $angle = ($value / $total) * 360;
      $end = $start + $angle;
      $large = $angle > 180 ? 1 : 0;
      $x1 = $cx + $radius * cos(deg2rad($start));
      $y1 = $cy + $radius * sin(deg2rad($start));
      $x2 = $cx + $radius * cos(deg2rad($end));
      $y2 = $cy + $radius * sin(deg2rad($end));
      $slices[] = ['path' => "M {$cx} {$cy} L {$x1} {$y1} A {$radius} {$radius} 0 {$large} 1 {$x2} {$y2} Z", 'angle' => $angle, 'color' => $colors[$index % count($colors)]];
      $start = $end;
  }
@endphp
<div class="rubros-pie-layout">
  <div class="rubros-pie-chart">
    @if($total > 0)
      <svg viewBox="0 0 300 300" role="img" aria-label="Distribución de comercios por rubro">
        @foreach($slices as $slice)
          @if($slice['angle'] >= 359.999)
            <circle cx="150" cy="150" r="112" fill="{{ $slice['color'] }}" />
          @else
            <path d="{{ $slice['path'] }}" fill="{{ $slice['color'] }}" stroke="#fff" stroke-width="1.4" />
          @endif
        @endforeach
        <circle cx="150" cy="150" r="49" fill="#fff" />
        <text x="150" y="146" text-anchor="middle" class="pie-total">{{ $total }}</text>
        <text x="150" y="165" text-anchor="middle" class="pie-caption">comercios</text>
      </svg>
    @else
      <div class="text-muted text-center py-5">No hay datos para los filtros seleccionados.</div>
    @endif
  </div>
  <div class="rubros-pie-legend">
    @foreach($items as $index => $item)
      <div class="pie-legend-row"><span class="pie-legend-color" style="background:{{ $colors[$index % count($colors)] }}"></span><span class="pie-legend-label">{{ data_get($item, 'rubro', data_get($item, 'subrubro', 'Sin rubro')) }}</span><strong>{{ data_get($item, 'cantidad', 0) }}</strong><small>{{ number_format((float) data_get($item, 'porcentaje', 0), 2, ',', '.') }}%</small></div>
    @endforeach
  </div>
</div>
