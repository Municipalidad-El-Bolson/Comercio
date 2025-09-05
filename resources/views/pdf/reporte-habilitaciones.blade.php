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
    th, td { border: 1px solid #ccc; padding: 6px 8px; }
    th { background: #f3f4f6; text-align: left; }
    .right { text-align: right; }
    .small { font-size: 11px; }
  </style>
</head>
<body>
  <h1>{{ $titulo ?? 'Reporte' }}</h1>
  <div class="muted small">
    Período: {{ $desde ?? '—' }} a {{ $hasta ?? '—' }} ·
    Generado: {{ now()->format('Y-m-d H:i') }}
  </div>

  <table>
    <thead>
      <tr>
        <th>Nombre</th>
        <th>Estado</th>
        <th>Subrubro</th>
        <th>Vto</th>
      </tr>
    </thead>
    <tbody>
      @forelse($items as $r)
        <tr>
          <td>{{ $r['nombre'] }}</td>
          <td>{{ $r['estado'] }}</td>
          <td>{{ $r['subrubro'] }}</td>
          <td>{{ $r['vto'] }}</td>
        </tr>
      @empty
        <tr><td colspan="4" class="small">Sin datos para los filtros seleccionados.</td></tr>
      @endforelse
    </tbody>
  </table>
</body>
</html>
