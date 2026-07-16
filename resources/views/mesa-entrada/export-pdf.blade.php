<!doctype html>
<html lang="es"><head><meta charset="utf-8"><style>
  @page { margin: 28px 30px; } body { font-family: DejaVu Sans, sans-serif; color:#263238; font-size:9px; }
  h1 { font-size:18px; margin:0 0 5px; color:#164e78; } .meta { margin-bottom:14px; color:#607d8b; }
  table { width:100%; border-collapse:collapse; table-layout:fixed; } th { background:#d9eaf7; color:#183b56; text-align:left; }
  th, td { border:1px solid #b8c7d1; padding:5px; vertical-align:top; overflow-wrap:break-word; } tr:nth-child(even) td { background:#f7fafc; }
  .fecha{width:8%}.numero{width:8%}.titular{width:19%}.hc{width:8%}.docs{width:31%}.usuario{width:13%}.carga{width:13%}
  footer { position:fixed; bottom:-18px; right:0; color:#78909c; }
</style></head><body>
  <h1>{{ $title }}</h1><div class="meta">Generado el {{ now()->format('d/m/Y H:i') }} · {{ $rows->count() }} registros</div>
  <table><thead><tr><th class="fecha">Fecha</th><th class="numero">Nº ingreso</th><th class="titular">Titular / Razón social</th><th class="hc">HC</th><th class="docs">Documentación</th><th class="usuario">Ingresó</th><th class="carga">Fecha de carga</th></tr></thead>
  <tbody>@forelse($rows as $row)<tr>@foreach($row as $value)<td>{{ $value ?: '—' }}</td>@endforeach</tr>@empty<tr><td colspan="7">No se encontraron registros para los filtros aplicados.</td></tr>@endforelse</tbody></table>
  <footer>Municipalidad de El Bolsón - Mesa de entrada</footer>
</body></html>
