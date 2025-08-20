<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Login</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
</head>
<body style="font-family:system-ui; background:#f6f7fb; display:flex; justify-content:center; align-items:center; height:100vh;">
  <form method="POST" action="{{ route('login.post') }}" style="background:#fff;padding:20px;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,.1); width:300px;">
    @csrf
    <h2 style="margin-bottom:16px;">Ingresar</h2>
    <div>
      <input type="email" name="email" value="{{ old('email') }}" placeholder="Email" required autofocus style="width:100%;padding:8px;margin-bottom:10px;">
    </div>
    <div>
      <input type="password" name="password" placeholder="Contraseña" required style="width:100%;padding:8px;margin-bottom:10px;">
    </div>
    <div style="margin-bottom:10px;">
      <label><input type="checkbox" name="remember"> Recordarme</label>
    </div>
    @if ($errors->any())
      <div style="color:#d00;margin-bottom:10px;">{{ $errors->first() }}</div>
    @endif
    <button type="submit" style="width:100%;padding:10px;background:#2563eb;color:#fff;border:none;border-radius:6px;">Entrar</button>
  </form>
</body>
</html>
