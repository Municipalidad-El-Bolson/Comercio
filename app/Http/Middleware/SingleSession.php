<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SingleSession
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check()) {
            $user    = auth()->user();
            $current = session()->getId();

            $isLivewire = $request->headers->has('X-Livewire')
                        || $request->routeIs('livewire.*')
                        || $request->is('livewire/*');

            if ($user->current_session_id && $user->current_session_id !== $current) {


                auth()->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                if ($isLivewire) {
                    
                    return response()->json([
                        'message'  => 'Tu cuenta tiene otra sesión activa.',
                        'redirect' => route('login'),
                    ], 419);
                }

                
                return redirect()
                    ->route('login')
                    ->withErrors(['email' => 'Tu cuenta tiene otra sesión activa.']);
            }

           
            if (
                $user->current_session_id !== $current
                || now()->diffInMinutes($user->last_seen_at ?? now()) >= 2
            ) {
                $user->forceFill([
                    'current_session_id' => $current,
                    'last_seen_at'       => now(),
                ])->saveQuietly();
            }
        }

        return $next($request);
    }
}
