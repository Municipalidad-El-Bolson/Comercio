<!doctype html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Usuarios</title></head>
<body>
  <h1>Usuarios</h1>
  @if(session('ok')) <div>{{ session('ok') }}</div> @endif
  <p><a href="{{ route('admin.users.create') }}">+ Nuevo usuario</a></p>
  <table border="1" cellpadding="6">
    <tr><th>Nombre</th><th>Email</th><th>Rol</th><th>Creado</th></tr>
    @foreach($users as $u)
      <tr>
        <td>{{ $u->name }}</td>
        <td>{{ $u->email }}</td>
        <td>{{ $u->role }}</td>
        <td>{{ $u->created_at }}</td>
      </tr>
    @endforeach
  </table>
  {{ $users->links() }}
  <p><a href="{{ route('panel') }}">Volver</a></p>
</body>
</html>