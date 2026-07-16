<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <style>
    @page { margin: 20px 22px 25px; }
    body { font-family: DejaVu Sans, sans-serif; color: #172033; font-size: 7px; }
    h1 { margin: 0 0 3px; font-size: 16px; color: #173b63; }
    .meta { color: #64748b; margin-bottom: 6px; }
    .filters { background: #edf3f8; border: 1px solid #cbd8e5; padding: 5px 7px; margin-bottom: 7px; }
    .filters span { margin-right: 11px; white-space: nowrap; }
    .warning { background: #fff3cd; border: 1px solid #ffe69c; padding: 5px 7px; margin-bottom: 7px; }
    table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    thead { display: table-header-group; }
    tr { page-break-inside: avoid; }
    th { background: #173b63; color: white; padding: 4px 3px; text-align: left; font-size: 6.5px; }
    td { border: 1px solid #d7dee8; padding: 3px; vertical-align: top; overflow-wrap: break-word; }
    tbody tr:nth-child(even) { background: #f7f9fb; }
    .center { text-align: center; }
    .page-number:after { content: counter(page); }
    footer { position: fixed; bottom: -17px; left: 0; right: 0; text-align: right; color: #64748b; }
  </style>
</head>
<body>
  <h1>Reporte de habilitaciones comerciales</h1>
  <div class="meta">Municipalidad de El Bolsón - Generado el {{ now()->format('d/m/Y H:i') }} - {{ $total }} registros encontrados</div>
  <div class="filters">
    @foreach($filterLabels as $label => $value)<span><strong>{{ $label }}:</strong> {{ $value }}</span>@endforeach
  </div>
  @if($truncated)
    <div class="warning">El PDF muestra los primeros {{ $items->count() }} de {{ $total }} registros. Para obtener el listado completo, descargue el Excel.</div>
  @endif

  <table>
    <thead>
      <tr>
        <th style="width:8%">Nombre comercial</th>
        <th style="width:10%">Titular</th>
        <th style="width:5%">HC</th>
        <th style="width:18%">Rubro principal</th>
        <th style="width:8%">Trámite</th>
        <th style="width:6%">Fecha</th>
        <th style="width:11%">Dirección</th>
        <th style="width:8%">Teléfono</th>
        <th style="width:4%">Unid.</th>
        <th style="width:4%">Plazas</th>
        <th style="width:7%">Situación</th>
        <th style="width:6%">Susp. desde</th>
        <th style="width:6%">Susp. hasta</th>
      </tr>
    </thead>
    <tbody>
      @forelse($items as $item)
        <tr>
          <td>{{ $item['nombre_comercial'] }}</td><td>{{ $item['titular'] }}</td><td>{{ $item['hc'] }}</td>
          <td>{{ $item['rubros'] }}</td><td>{{ $item['tramite'] }}</td><td>{{ $item['fecha_asociada'] }}</td>
          <td>{{ $item['direccion'] }}</td><td>{{ $item['telefonos'] }}</td>
          <td class="center">{{ $item['unidades'] ?? '-' }}</td><td class="center">{{ $item['plazas'] ?? '-' }}</td>
          <td>{{ $item['situacion'] }}</td><td>{{ $item['suspension_desde'] }}</td><td>{{ $item['suspension_hasta'] }}</td>
        </tr>
      @empty
        <tr><td colspan="13" class="center">Sin datos para los filtros seleccionados.</td></tr>
      @endforelse
    </tbody>
  </table>
  <footer>Página <span class="page-number"></span></footer>
</body>
</html>
