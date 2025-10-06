<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Registrar usuarios</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  {{-- Bootstrap 5 --}}
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://kit.fontawesome.com/a2e0e6ad1a.js" crossorigin="anonymous"></script>
  <style>
    body { background:#f6f7fb; }
    .card { border: none; border-radius: .75rem; }
    .content-header h1 {
      font-size: 1.4rem;
      font-weight: 600;
      color: #0d6efd;
    }
    .btn-primary {
      background-color: #2563eb;
      border-color: #2563eb;
    }
    .btn-primary:hover {
      background-color: #1d4ed8;
      border-color: #1d4ed8;
    }
  </style>
</head>

<body class="d-flex align-items-center justify-content-center min-vh-100">

  <div class="container py-4">
    <div class="row justify-content-center">
      <div class="col-12 col-md-8 col-lg-6">

        <div class="text-center mb-3">
          <h1 class="m-0 pb-2 border-bottom">Registrar usuarios</h1>
        </div>

        <div class="card shadow-sm">
          <div class="card-body">

            {{-- Mensaje de estado --}}
            @if (session('status'))
              <div class="alert alert-success py-2 mb-3">
                {{ session('status') }}
              </div>
            @endif

            {{-- Formulario --}}
            <form wire:submit.prevent="submit" class="row g-3">

              {{-- Nombre --}}
              <div class="col-12">
                <label for="name" class="form-label fw-medium">Nombre</label>
                <input id="name" type="text" wire:model.defer="name"
                       class="form-control form-control-sm @error('name') is-invalid @enderror"
                       placeholder="Nombre completo">
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              {{-- Email --}}
              <div class="col-12">
                <label for="email" class="form-label fw-medium">Email</label>
                <input id="email" type="email" wire:model.defer="email"
                       class="form-control form-control-sm @error('email') is-invalid @enderror"
                       placeholder="correo@ejemplo.com">
                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              {{-- Contraseña --}}
              <div class="col-md-6">
                <label for="password" class="form-label fw-medium">Contraseña</label>
                <input id="password" type="password" wire:model.defer="password"
                       class="form-control form-control-sm @error('password') is-invalid @enderror"
                       placeholder="••••••••">
                @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              {{-- Rol --}}
              <div class="col-md-6">
                <label for="role" class="form-label fw-medium">Rol</label>
                <select id="role" wire:model="role"
                        class="form-select form-select-sm @error('role') is-invalid @enderror">
                  <option value="" disabled>Seleccioná un rol</option>
                  @foreach ($roleOptions as $opt)
                    <option value="{{ $opt['value'] }}">{{ $opt['label'] }}</option>
                  @endforeach
                </select>
                @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              {{-- Confirmar contraseña --}}
              <div class="col-md-6">
                <label for="password_confirmation" class="form-label fw-medium">Confirmar contraseña</label>
                <input id="password_confirmation" type="password" wire:model.defer="password_confirmation"
                       class="form-control form-control-sm" placeholder="Repetir contraseña">
              </div>

              {{-- Botón Registrar --}}
              <div class="col-md-6 d-flex align-items-end justify-content-end">
                <button type="submit" class="btn btn-primary w-100">
                  <i class="fas fa-user-plus me-1"></i> Registrar
                </button>
              </div>

            </form>
          </div>
        </div>

      </div>
    </div>
  </div>

  {{-- Bootstrap JS --}}
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
