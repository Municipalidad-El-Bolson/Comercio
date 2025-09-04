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
use App\Livewire\Auth\RegisterUser;
use App\Livewire\Admin\UsersIndex;

Route::redirect('/', '/login');

Route::get('/login',  [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {

    // “Home” post-login: derivamos según rol (ver punto 5)
    Route::get('/panel', fn () => redirect()->route('mapas'))->name('panel');

    // Visible para todos los logueados, pero acceso controlado por middleware de rol
    Route::middleware('role:admin,writer,reader')->group(function () {
        Route::get('/mapas', ComercioMapa::class)->name('mapas');
    });

    Route::middleware('role:admin,writer')->group(function () {
        Route::get('/ubicaciones', Ubicaciones::class)->name('ubicaciones');
        Route::get('/comercios/{ubicacion}', ComercioData::class)->name('comercio.data');
    });

    Route::middleware('role:admin')->group(function () {
        Route::get('/historial', Historial::class)->name('historial');
        Route::get('/reportes', Reportes::class)->name('reportes');
        Route::get('/register-user', RegisterUser::class)->name('register-user');
        Route::get('/usuarios', UsersIndex::class)->name('users.index');
    });

    // Archivos (si deben ser privados, dejalos bajo auth)
    Route::get('/files/{path}', function (string $path) {
        $path = ltrim($path, '/');
        abort_unless(Storage::disk('public')->exists($path), 404);
        return Storage::disk('public')->response($path);
    })->where('path', '.*')->name('files.show');
});