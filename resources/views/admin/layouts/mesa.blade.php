<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Mesa de entrada</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  {{-- Bootstrap 5 si ya lo usás en el resto --}}
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

    {{-- Alpine.js para dropdown interactivo --}}
    <script src="https://unpkg.com/alpinejs@3.x.x" defer></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js" defer></script>
    @livewireStyles

</head>
<body style="background:#f6f7fb;">

  {{-- Header mínimo con sólo logout --}}
  <nav class="navbar navbar-light bg-white border-bottom shadow-sm">
    <div class="container-fluid">
      <span class="navbar-brand mb-0 h1">Mesa de entrada</span>

      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button class="btn btn-outline-danger btn-sm" type="submit">
          Cerrar sesión
        </button>
      </form>
    </div>
  </nav>

  {{-- Contenido del componente --}}
  <main class="container my-4">
    {{ $slot }}
  </main>
    {{-- Contenido principal --}}
    @livewireScripts
</body>
</html>
