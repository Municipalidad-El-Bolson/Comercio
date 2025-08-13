<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Comercio\Ubicaciones;
use App\Livewire\Counter;
use App\Livewire\Comercio\ComercioMapa;

// Route::get('/', function () {
//     return view('admin.index');
// });

Route::get('mapas', ComercioMapa::class)->name('mapas');
Route::get('/', Ubicaciones::class)->name('ubicaciones');
