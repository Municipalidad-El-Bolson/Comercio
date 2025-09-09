<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>{{ $titulo ?? 'Reporte' }}</title>
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; }
    h1 { font-size: 18px; margin: 0 0 6px; }
    .muted { color: #666; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { border: 1px solid #ccc; padding: 6px 8px; vertical-align: top; }
    th { background: #f3f4f6; text-align: left; }
    .right { text-align: right; }
    .small { font-size: 11px; }
    .filters { margin-top: 4px; }
    .filters span { margin-right: 12px; }
  </style>
</head>
<body>
  <h1>{{ $titulo ?? 'Reporte' }}</h1>

  {{-- Metadatos y filtros aplicados --}}
  <div class="muted small">
    Generado: {{ now()->format('Y-m-d H:i') }}
  </div>
  @if(!empty($filtros ?? []))
    <div class="muted small filters">
      <strong>Filtros aplicados:</strong>
      @foreach($filtros as $k => $v)
        <span>{{ $k }}: <em>{{ $v ?: '—' }}</em></span>
      @endforeach
    </div>
  @else
    <div class="muted small">Período: {{ $desde ?? '—' }} a {{ $hasta ?? '—' }}</div>
  @endif

  <table>
    <thead>
      <tr>
        <th>Nombre de fantasía</th>
        <th>Titular</th>
        <th>Teléfono/s</th>
        <th>Vencimiento</th>
        <th>Dirección</th>
        <th>Subrubro</th>
      </tr>
    </thead>
    <tbody>
      @forelse($items as $r)
        <tr>
          <td>{{ $r['fantasia'] }}</td>
          <td>{{ $r['titular'] }}</td>
          <td>{{ $r['telefonos'] }}</td>
          <td>{{ $r['vto'] }}</td>
          <td>{{ $r['direccion'] }}</td>
          <td>{{ $r['subrubro'] }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="6" class="small">Sin datos para los filtros seleccionados.</td>
        </tr>
      @endforelse
    </tbody>
  </table>
</body>
</html>
