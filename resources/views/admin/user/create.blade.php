<!doctype html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Crear usuario</title></head>
<body>
  <h1>Nuevo usuario</h1>
  <form method="POST" action="{{ route('admin.users.store') }}">
    @csrf
    <p><input name="name" value="{{ old('name') }}" placeholder="Nombre completo" required></p>
    <p><input type="email" name="email" value="{{ old('email') }}" placeholder="Email" required></p>
    <p><input type="password" name="password" placeholder="Contraseña (min 8)" required></p>
    <p><input type="password" name="password_confirmation" placeholder="Repetir contraseña" required></p>
    <p>
      <select name="role" required>
        <option value="">-- Rol --</option>
        <option value="admin"  @selected(old('role')==='admin')>Administrador</option>
        <option value="writer" @selected(old('role')==='writer')>Escritor</option>
        <option value="reader" @selected(old('role')==='reader')>Lector</option>
      </select>
    </p>
    @if ($errors->any())
      <div style="color:#d00">{{ $errors->first() }}</div>
    @endif
    <button type="submit">Crear</button>
  </form>
  <p><a href="{{ route('admin.users.index') }}">Volver</a></p>
</body>
</html>