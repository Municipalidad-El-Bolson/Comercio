<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Comercio\Ubicaciones;
use App\Livewire\Comercio\Historial;
use App\Livewire\Comercio\Reportes;
use App\Livewire\Comercio\ComercioMapa;

// /  → si está logueado va a /comercios, si no a /register
Route::get('/', function () {
    return auth()->check()
        ? redirect()->to('comercios')
        : redirect()->route('register');
})->name('home');

// Rutas protegidas
Route::middleware(['auth'])->group(function () {
    // Tu app principal
    Route::get('/comercios', Ubicaciones::class)->name('comercios');

    // Reportes (usa Reportes.php y reportes.blade.php)
    Route::get('/reportes', Reportes::class)->name('reportes');

    // Mapas (usa ComercioMapa.php y comercio-mapa.blade.php)
    Route::get('/mapas', ComercioMapa::class)->name('mapas');

    // Historial (solo admin si lo limitás con role/permission)
    Route::get('/historial', Historial::class)
        ->middleware('permission:historial.view')
        ->name('historial');
});

// Mantener rutas de auth (Volt/Breeze)
require __DIR__.'/auth.php';

