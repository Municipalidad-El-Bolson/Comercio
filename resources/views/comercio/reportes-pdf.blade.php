<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <style>
    @page { margin: 22px 28px 26px; }
    body { font-family: DejaVu Sans, sans-serif; color: #172033; font-size: 9px; }
    h1 { margin: 0 0 4px; font-size: 18px; color: #173b63; }
    .meta { color: #64748b; margin-bottom: 7px; }
    .filters { background: #edf3f8; border: 1px solid #cbd8e5; padding: 6px 8px; margin-bottom: 9px; }
    .filters span { margin-right: 14px; white-space: nowrap; }
    .record { width: 100%; border-collapse: collapse; margin-bottom: 8px; page-break-inside: avoid; }
    .record td { border: 1px solid #cfd8e3; padding: 5px 6px; vertical-align: top; }
    .record .head { background: #173b63; color: white; font-weight: bold; }
    .label { display: block; color: #52647a; font-size: 7px; font-weight: bold; text-transform: uppercase; margin-bottom: 2px; }
    .head .label { color: #dce9f5; }
    .rubros { line-height: 1.35; }
    .empty { padding: 35px; border: 1px solid #cfd8e3; text-align: center; color: #64748b; }
    .page-number:after { content: counter(page); }
    footer { position: fixed; bottom: -18px; left: 0; right: 0; text-align: right; color: #64748b; font-size: 8px; }
  </style>
</head>
<body>
  <h1>Reporte de habilitaciones comerciales</h1>
  <div class="meta">Municipalidad de El Bolsón · Generado el {{ now()->format('d/m/Y H:i') }} · {{ $items->count() }} registros</div>
  <div class="filters">
    @foreach($filters as $label => $value)<span><strong>{{ $label }}:</strong> {{ $value }}</span>@endforeach
  </div>

  @forelse($items as $item)
    <table class="record">
      <tr>
        <td class="head" style="width: 52%"><span class="label">Titular completo</span>{{ $item['titular'] }}</td>
        <td class="head" style="width: 16%"><span class="label">HC</span>{{ $item['hc'] }}</td>
        <td class="head" style="width: 16%"><span class="label">Situación</span>{{ $item['situacion'] }}</td>
        <td class="head" style="width: 16%"><span class="label">Fecha asociada</span>{{ $item['fecha_asociada'] }}</td>
      </tr>
      <tr><td colspan="4" class="rubros"><span class="label">Rubro completo (principal y anexos)</span>{{ $item['rubros'] }}</td></tr>
      <tr>
        <td colspan="2"><span class="label">Trámite al que corresponde la fecha</span>{{ $item['tramite'] }}</td>
        <td colspan="2"><span class="label">Dirección</span>{{ $item['direccion'] }}</td>
      </tr>
      <tr>
        <td colspan="2"><span class="label">Teléfono/s</span>{{ $item['telefonos'] }}</td>
        <td><span class="label">Unidades de alojamiento</span>{{ $item['unidades'] }}</td>
        <td><span class="label">Plazas de alojamiento</span>{{ $item['plazas'] }}</td>
      </tr>
      <tr>
        <td colspan="2"><span class="label">Suspensión de tasas desde</span>{{ $item['suspension_desde'] }}</td>
        <td colspan="2"><span class="label">Suspensión de tasas hasta</span>{{ $item['suspension_hasta'] }}</td>
      </tr>
    </table>
  @empty
    <div class="empty">Sin datos para los filtros seleccionados.</div>
  @endforelse

  <footer>Página <span class="page-number"></span></footer>
</body>
</html>
