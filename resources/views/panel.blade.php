<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Panel</title>
</head>
<body>
  <header style="display:flex;justify-content:space-between;align-items:center;padding:12px;background:#f3f4f6">
    <div>👋 Hola, {{ auth()->user()->name }} ({{ auth()->user()->role }})</div>
    <form method="POST" action="{{ route('logout') }}">@csrf
      <button>Salir</button>
    </form>
  </header>

  <main style="padding:16px">
    <h1>Tu programa acá</h1>
    @if(auth()->user()->isAdmin())
      <p><a href="{{ route('admin.users.index') }}">Administrar usuarios</a></p>
    @endif
  </main>
</body>
</html>