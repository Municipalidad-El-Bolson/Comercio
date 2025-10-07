<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SingleSession
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check()) {
            $user = auth()->user();
            $current = session()->getId();

            if ($user->current_session_id && $user->current_session_id !== $current) {
                auth()->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')
                    ->withErrors(['email' => 'Tu cuenta tiene otra sesión activa.']);
            }

            if ($user->current_session_id !== $current || now()->diffInMinutes($user->last_seen_at ?? now()) >= 2) {
                $user->forceFill([
                    'current_session_id' => $current,
                    'last_seen_at'       => now(),
                ])->saveQuietly();
            }
        }
        return $next($request);
    }
}