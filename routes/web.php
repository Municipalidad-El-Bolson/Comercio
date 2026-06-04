<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

use App\Http\Controllers\AuthController;

use App\Livewire\Comercio\Ubicaciones;
use App\Livewire\Comercio\ComercioMapa;
use App\Livewire\Comercio\Historial;
use App\Livewire\Comercio\Reportes;
use App\Livewire\Comercio\ComercioData;
use App\Livewire\Auth\RegisterUser;
use App\Livewire\Admin\UsersIndex;
use App\Livewire\MesaEntrada\Form as MesaForm;
use App\Livewire\MesaEntrada\Inbox as MesaInbox;
use App\Livewire\Vencimientos\ProximosIndex;
use App\Livewire\Vencimientos\VencidosIndex;

Route::redirect('/', '/login');

Route::get('/login',  [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ----- ZONA PROTEGIDA -----
Route::middleware('auth')->group(function () {

    Route::get('/panel', function () {
        $u = auth()->user();
        return match ($u->role) {
            'admin'            => redirect()->route('users.index'),
            'writer', 'reader' => redirect()->route('mapas'),
            'mesa'             => redirect()->route('mesa.form'),
            default            => redirect()->route('login'),
        };
    })->name('panel');

    Route::get('/mesa/enviar', MesaForm::class)
        ->middleware('can:mesa-entrada-send') // solo 'mesa' (y admin si querés)
        ->name('mesa.form');
 

    /** Mapas (mesa NO entra) */
    Route::middleware('role:admin,writer,reader')->group(function () {
        Route::get('/mapas', ComercioMapa::class)->name('mapas');
    });

    /** Writer+Admin */
    Route::middleware('role:admin,writer')->group(function () {
        Route::get('/ubicaciones', Ubicaciones::class)->name('ubicaciones');
        Route::get('/comercios/{ubicacion}', ComercioData::class)->name('comercio.data');
        Route::get('/mesa', MesaInbox::class)->name('mesa.inbox'); 
        Route::get('/vencimientos/proximos', ProximosIndex::class)->name('prox_vto.index');
        Route::get('/vencimientos/vencidos',  VencidosIndex::class)->name('vto.index');
    });

    /** Solo Admin */
    Route::middleware('role:admin')->group(function () {
        Route::get('/historial', Historial::class)->name('historial');
        Route::get('/reportes', Reportes::class)->name('reportes');
        Route::get('/register-user', RegisterUser::class)->name('register-user');
        Route::get('/usuarios', UsersIndex::class)->name('users.index');
    });

    /** Archivos bajo auth */
    Route::get('/files/{path}', function (string $path) {
        $path = ltrim($path, '/');
        abort_unless(Storage::disk('public')->exists($path), 404);
        return Storage::disk('public')->response($path);
    })->where('path', '.*')->name('files.show');
});
