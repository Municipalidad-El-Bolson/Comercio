<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ubicacion;
use Illuminate\Http\Request;

class UbicacionController extends Controller
{
    public function index()
    {
        return response()->json(Ubicacion::all());
    }

    public function show($id)
    {
        return response()->json(Ubicacion::findOrFail($id));
    }

    public function store(Request $request)
    {
        $ubicacion = Ubicacion::create($request->all());
        return response()->json($ubicacion, 201);
    }

    public function update(Request $request, $id)
    {
        $ubicacion = Ubicacion::findOrFail($id);
        $ubicacion->update($request->all());
        return response()->json($ubicacion);
    }

    public function destroy($id)
    {
        Ubicacion::destroy($id);
        return response()->json(null, 204);
    }
}
