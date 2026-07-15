<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #222; }
    h1 { font-size: 17px; margin: 0 0 4px; }
    .meta { color: #555; margin-bottom: 14px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #bbb; padding: 5px; vertical-align: top; }
    th { background: #e9ecef; text-align: left; }
    tr:nth-child(even) { background: #f8f9fa; }
  </style>
</head>
<body>
  <h1>Historial completo del comercio</h1>
  <div class="meta">
    {{ $ubicacion->nombre_comercial ?: ($ubicacion->razon_social ?: 'Comercio #'.$ubicacion->id) }}
    · Generado: {{ now()->format('d/m/Y H:i') }}
  </div>
  <table>
    <thead>
      <tr><th>Fecha</th><th>Usuario</th><th>Acción</th><th>Campo</th><th>Anterior</th><th>Nuevo</th></tr>
    </thead>
    <tbody>
      @forelse($rows as $row)
        <tr>
          <td>{{ $row['fecha'] }}</td><td>{{ $row['usuario'] }}</td><td>{{ $row['accion'] }}</td>
          <td>{{ $row['campo'] }}</td><td>{{ $row['anterior'] }}</td><td>{{ $row['nuevo'] }}</td>
        </tr>
      @empty
        <tr><td colspan="6">No hay ediciones registradas.</td></tr>
      @endforelse
    </tbody>
  </table>
</body>
</html>
