<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>{{ $titulo ?? 'Reporte' }}</title>
  <style>
    body {
      font-family: DejaVu Sans, sans-serif;
      font-size: 12px;
      color: #1f2937;
      margin: 24px;
      line-height: 1.4;
    }
    h1 {
      font-size: 22px;
      margin: 0;
      color: #111827;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      border-bottom: 2px solid #2563eb;
      padding-bottom: 6px;
    }
    .meta {
      margin-top: 4px;
      font-size: 11px;
      color: #6b7280;
    }
    .filters {
      margin-top: 6px;
      font-size: 11px;
      color: #374151;
    }
    .filters strong {
      color: #111827;
    }
    .filters span {
      margin-right: 10px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 16px;
      border: 1px solid #d1d5db;
    }
    th {
      background-color: #f3f4f6;
      color: #111827;
      padding: 8px;
      border-bottom: 1px solid #d1d5db;
      text-align: left;
    }
    td {
      padding: 7px 8px;
      border-top: 1px solid #e5e7eb;
      vertical-align: top;
    }
    tr:nth-child(even) td {
      background-color: #fafafa;
    }
    .small { font-size: 11px; }
    .center { text-align: center; }
    .muted { color: #6b7280; }
    footer {
      margin-top: 20px;
      text-align: center;
      font-size: 10px;
      color: #9ca3af;
    }
  </style>
</head>
<body>
  <h1>{{ $titulo ?? 'Reporte' }}</h1>

  {{-- Información de generación y filtros --}}
  <div class="meta">
    Generado el {{ now()->format('d/m/Y H:i') }}
  </div>

  @if(!empty($filtros ?? []))
    <div class="filters">
      <strong>Filtros aplicados:</strong>
      @foreach($filtros as $k => $v)
        <span>{{ ucfirst($k) }}: <em>{{ $v ?: '—' }}</em></span>
      @endforeach
    </div>
  @elseif(!empty($desde) || !empty($hasta))
    <div class="filters">
      <strong>Período:</strong> {{ $desde ?? '—' }} a {{ $hasta ?? '—' }}
    </div>
  @endif

  {{-- Tabla de resultados --}}
  <table>
    <thead>
      <tr>
        <th>Nombre comercial</th>
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
          <td colspan="6" class="center small muted">
            Sin datos para los filtros seleccionados.
          </td>
        </tr>
      @endforelse
    </tbody>
  </table>

  <footer>
    Municipalidad de El Bolsón — Dirección de Sistemas<br>
    © {{ date('Y') }} Todos los derechos reservados
  </footer>
</body>
</html>
