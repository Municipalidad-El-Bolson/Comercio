<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;   
use App\Models\AuditLog; 

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('panel');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        // 1) Validar
        $cred = $request->validate([
            'email'    => ['required','email'],
            'password' => ['required','string'],
        ]);

        // 2) Intentar autenticación (SIN chequeo previo de current_session_id)
        if (Auth::attempt($cred, $request->boolean('remember'))) {
            // Sesión nueva
            $request->session()->regenerate();

            /** @var \App\Models\User $user */
            $user = auth()->user();

            // 3) (Opcional recomendado) Invalidar otras sesiones del mismo usuario
            //    Esto cambia el remember_token, tirando abajo sesiones viejas "remember me"
            try {
                Auth::logoutOtherDevices($cred['password']);
            } catch (\Throwable $e) {
                // si tu driver/hasher no lo soporta, simplemente ignorá
            }

            // 4) Guardar la sesión ACTUAL en DB (pisa cualquier valor viejo)
            $user->forceFill([
                'current_session_id' => session()->getId(),
                'last_seen_at'       => now(),
            ])->save();

            // 5) Auditoría login OK
            AuditLog::create([
                'user_id'     => $user->id,
                'action'      => 'Inicio de sesión',
                'entity_type' => null,
                'entity_id'   => null,
                'ip'          => $request->ip(),
                'method'      => $request->method(),
                'path'        => $request->path(),
                'meta'        => [
                    'route'  => $request->route()?->getName(),
                    'action' => 'login',
                ],
            ]);

            return redirect()->intended(route('panel'));
        }

        // 6) Auditoría login fallido
        AuditLog::create([
            'user_id'     => null,
            'action'      => 'Intento fallido de inicio de sesión',
            'entity_type' => null,
            'entity_id'   => null,
            'ip'          => $request->ip(),
            'method'      => $request->method(),
            'path'        => $request->path(),
            'meta'        => [
                'route'  => $request->route()?->getName(),
                'action' => 'login_failed',
                'email'  => $cred['email'],
            ],
        ]);

        return back()
            ->withErrors(['email' => 'Credenciales inválidas.'])
            ->onlyInput('email');
    }

    public function logout(Request $request)
    {
        if (auth()->check()) {
            AuditLog::create([
                'user_id'     => auth()->id(),
                'action'      => 'Cierre de sesión',
                'entity_type' => null,
                'entity_id'   => null,
                'ip'          => $request->ip(),
                'method'      => $request->method(),
                'path'        => $request->path(),
                'meta'        => ['route' => 'logout', 'action' => 'logout'],
            ]);

            auth()->user()->update(['current_session_id' => null]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
