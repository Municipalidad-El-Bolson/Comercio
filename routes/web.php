<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Comercio\ReportesPdfController;
use App\Http\Controllers\Comercio\ComercioHistorialExportController;

use App\Livewire\Comercio\Ubicaciones;
use App\Livewire\Comercio\ComercioMapa;
use App\Livewire\Comercio\Historial;
use App\Livewire\Comercio\Reportes;
use App\Livewire\Comercio\ComercioData;
use App\Livewire\Comercio\ActasSeguimiento;
use App\Livewire\Admin\UsersIndex;
use App\Livewire\MesaEntrada\Form as MesaForm;
use App\Livewire\MesaEntrada\Inbox as MesaInbox;
use App\Livewire\MesaEntrada\Historial as MesaHistorial;
use App\Http\Controllers\MesaEntrada\ExportController as MesaEntradaExportController;
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

    Route::get('/mesa', MesaInbox::class)
        ->middleware('can:mesa-entrada-view')
        ->name('mesa.inbox');
    Route::get('/mesa/historial', MesaHistorial::class)->middleware('can:mesa-entrada-view')->name('mesa.historial');
    Route::get('/mesa/excel', [MesaEntradaExportController::class, 'inboxExcel'])->middleware('can:mesa-entrada-view')->name('mesa.inbox.excel');
    Route::get('/mesa/pdf', [MesaEntradaExportController::class, 'inboxPdf'])->middleware('can:mesa-entrada-view')->name('mesa.inbox.pdf');
    Route::get('/mesa/historial/excel', [MesaEntradaExportController::class, 'historialExcel'])->middleware('can:mesa-entrada-view')->name('mesa.historial.excel');
    Route::get('/mesa/historial/pdf', [MesaEntradaExportController::class, 'historialPdf'])->middleware('can:mesa-entrada-view')->name('mesa.historial.pdf');
 

    /** Mapas (mesa NO entra) */
    Route::middleware('role:admin,writer,reader')->group(function () {
        Route::get('/mapas', ComercioMapa::class)->name('mapas');
        Route::get('/ubicaciones', Ubicaciones::class)->name('ubicaciones');
        Route::get('/comercios/{ubicacion}', ComercioData::class)->name('comercio.data');
        Route::get('/comercios/{ubicacion}/historial/excel', [ComercioHistorialExportController::class, 'excel'])->name('comercio.historial.excel');
        Route::get('/comercios/{ubicacion}/historial/pdf', [ComercioHistorialExportController::class, 'pdf'])->name('comercio.historial.pdf');
    });

    /** Writer+Admin */
    Route::middleware('role:admin,writer')->group(function () {
        Route::get('/actas/seguimiento', ActasSeguimiento::class)->name('actas.seguimiento');
        Route::get('/vencimientos/proximos', ProximosIndex::class)->name('prox_vto.index');
        Route::get('/vencimientos/vencidos',  VencidosIndex::class)->name('vto.index');
    });

    /** Solo Admin */
    Route::middleware('role:admin')->group(function () {
        Route::get('/historial', Historial::class)->name('historial');
        Route::get('/reportes', Reportes::class)->name('reportes');
        Route::get('/reportes/pdf', ReportesPdfController::class)->name('reportes.pdf');
        Route::redirect('/register-user', '/usuarios')->name('register-user');
        Route::get('/usuarios', UsersIndex::class)->name('users.index');
    });

    /** Archivos bajo auth */
    Route::get('/files/{path}', function (string $path) {
        $path = ltrim($path, '/');
        abort_unless(Storage::disk('public')->exists($path), 404);
        return Storage::disk('public')->response($path);
    })->where('path', '.*')->name('files.show');
});
