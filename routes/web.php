<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Comercio\Ubicaciones;
use App\Livewire\Counter;
use App\Livewire\Comercio\ComercioMapa;
use App\Livewire\Comercio\Historial;
use App\Livewire\Comercio\Reportes;
use App\Livewire\Comercio\ComercioData;

// Route::get('/', function () {
//     return view('admin.index');
// });

Route::get('mapas', ComercioMapa::class)->name('mapas');
Route::get('historial', Historial::class)->name('historial');
Route::get('reportes', Reportes::class)->name('reportes');
Route::get('/', Ubicaciones::class)->name('ubicaciones');
Route::get('/comercios/{ubicacion}', ComercioData::class)->name('comercio.data');
