<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

// Auth básico (controlador que ya definimos)
use App\Http\Controllers\AuthController;

// Tus componentes Livewire
use App\Livewire\Comercio\Ubicaciones;
use App\Livewire\Comercio\ComercioMapa;
use App\Livewire\Comercio\Historial;
use App\Livewire\Comercio\Reportes;
use App\Livewire\Comercio\ComercioData;

Route::redirect('/', '/login');

Route::get('/login',  [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {

    // Tu “home” del sistema: Ubicaciones (post‑login cae acá)
    Route::get('/panel', Ubicaciones::class)->name('panel');

    // Alias opcional por compatibilidad con tu código anterior
    Route::get('/ubicaciones', Ubicaciones::class)->name('ubicaciones');

    Route::get('/mapas', ComercioMapa::class)->name('mapas');
    Route::get('/historial', Historial::class)->name('historial');
    Route::get('/reportes', Reportes::class)->name('reportes');

    Route::get('/comercios/{ubicacion}', ComercioData::class)->name('comercio.data');

    // Archivos públicos (si querés que sólo los vean usuarios logueados, dejalo aquí)
    Route::get('/files/{path}', function (string $path) {
        $path = ltrim($path, '/');
        abort_unless(Storage::disk('public')->exists($path), 404);
        return Storage::disk('public')->response($path);
    })->where('path', '.*')->name('files.show');
});