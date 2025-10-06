<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Login</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  {{-- Bootstrap 5 --}}
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background:#f6f7fb; }
    .login-card { max-width: 380px; }
  </style>
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100">

  <main class="container">
    <div class="row justify-content-center">
      <div class="col-12 d-flex justify-content-center">

        <div class="card login-card shadow-sm border-0 w-100">
          <div class="card-body p-4">

            <div class="text-center mb-3">
              <h1 class="h4 mb-0">Ingresar</h1>
            </div>

            {{-- Estado / Errores --}}
            @if (session('status'))
              <div class="alert alert-success py-2 mb-3">
                {{ session('status') }}
              </div>
            @endif

            @if ($errors->any())
              <div class="alert alert-danger py-2 mb-3">
                {{ $errors->first() }}
              </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}" autocomplete="off" novalidate class="row g-3">
              @csrf

              <div class="col-12">
                <label for="email" class="form-label fw-medium">Email</label>
                <input id="email" type="email" name="email"
                       value="{{ old('email') }}"
                       class="form-control form-control-sm @error('email') is-invalid @enderror"
                       placeholder="correo@ejemplo.com" required autofocus>
                @error('email')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="col-12">
                <label for="password" class="form-label fw-medium">Contraseña</label>
                <input id="password" type="password" name="password"
                       class="form-control form-control-sm @error('password') is-invalid @enderror"
                       placeholder="••••••••" required>
                @error('password')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="col-12 d-flex justify-content-between align-items-center">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" value="1" id="remember" name="remember"
                         {{ old('remember') ? 'checked' : '' }}>
                  <label class="form-check-label" for="remember">Recordarme</label>
                </div>
              </div>

              <div class="col-12">
                <button type="submit" class="btn btn-primary w-100">
                  Entrar
                </button>
              </div>
            </form>

          </div>
        </div>

      </div>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
