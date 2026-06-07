<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Terreno;
use App\Models\Finca;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class TerrenoController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Terreno::with('finca');

        if ($request->has('id_finca')) {
            $query->forFinca($request->id_finca);
        }

        if ($request->has('relieve')) {
            $query->byRelieve($request->relieve);
        }

        if (!$user->isAdmin()) {
            if ($user->isPropietario()) {
                $query->whereHas('finca', fn($q) => $q->where('id_Propietario', $user->propietario->id));
            } else {
                return response()->json(['success' => false, 'message' => 'Sin permisos'], Response::HTTP_FORBIDDEN);
            }
        }

        $terrenos = $query->paginate(15);

        return response()->json([
            'success'    => true,
            'message'    => 'Lista de terrenos',
            'data'       => $terrenos->items(),
            'pagination' => [
                'current_page' => $terrenos->currentPage(),
                'last_page'    => $terrenos->lastPage(),
                'per_page'     => $terrenos->perPage(),
                'total'        => $terrenos->total(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_Finca'        => 'required|exists:finca,id_Finca',
            'Superficie'      => 'nullable|numeric|min:0',
            'Relieve'         => 'nullable|string|max:30',
            'Suelo_Textura'   => 'nullable|string|max:30',
            'ph_Suelo'        => 'nullable|string|max:20',
            'Precipitacion'   => 'nullable|numeric',
            'Velocidad_Viento'=> 'nullable|numeric',
            'Temp_Anual'      => 'nullable|numeric',
            'Temp_Min'        => 'nullable|numeric',
            'Temp_Max'        => 'nullable|numeric',
            'Radiacion'       => 'nullable|numeric',
            'Fuente_Agua'     => 'nullable|string|max:30',
            'Caudal_Disponible'=> 'nullable|integer',
            'Riego_Metodo'    => 'nullable|string|max:30',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $terreno = Terreno::create($request->only([
            'id_Finca', 'Superficie', 'Relieve', 'Suelo_Textura', 'ph_Suelo',
            'Precipitacion', 'Velocidad_Viento', 'Temp_Anual', 'Temp_Min',
            'Temp_Max', 'Radiacion', 'Fuente_Agua', 'Caudal_Disponible', 'Riego_Metodo',
        ]));

        return response()->json(['success' => true, 'message' => 'Terreno creado', 'data' => $terreno], Response::HTTP_CREATED);
    }

    public function show($id)
    {
        $terreno = Terreno::with('finca')->find($id);
        if (!$terreno) {
            return response()->json(['success' => false, 'message' => 'Terreno no encontrado'], Response::HTTP_NOT_FOUND);
        }
        return response()->json(['success' => true, 'data' => $terreno]);
    }

    public function update(Request $request, $id)
    {
        $terreno = Terreno::find($id);
        if (!$terreno) {
            return response()->json(['success' => false, 'message' => 'Terreno no encontrado'], Response::HTTP_NOT_FOUND);
        }

        $validator = Validator::make($request->all(), [
            'Superficie'       => 'nullable|numeric|min:0',
            'Relieve'          => 'nullable|string|max:30',
            'Suelo_Textura'    => 'nullable|string|max:30',
            'ph_Suelo'         => 'nullable|string|max:20',
            'Precipitacion'    => 'nullable|numeric',
            'Velocidad_Viento' => 'nullable|numeric',
            'Temp_Anual'       => 'nullable|numeric',
            'Temp_Min'         => 'nullable|numeric',
            'Temp_Max'         => 'nullable|numeric',
            'Radiacion'        => 'nullable|numeric',
            'Fuente_Agua'      => 'nullable|string|max:30',
            'Caudal_Disponible'=> 'nullable|integer',
            'Riego_Metodo'     => 'nullable|string|max:30',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $terreno->update($request->only([
            'Superficie', 'Relieve', 'Suelo_Textura', 'ph_Suelo', 'Precipitacion',
            'Velocidad_Viento', 'Temp_Anual', 'Temp_Min', 'Temp_Max', 'Radiacion',
            'Fuente_Agua', 'Caudal_Disponible', 'Riego_Metodo',
        ]));

        return response()->json(['success' => true, 'message' => 'Terreno actualizado', 'data' => $terreno]);
    }

    public function destroy($id)
    {
        $terreno = Terreno::find($id);
        if (!$terreno) {
            return response()->json(['success' => false, 'message' => 'Terreno no encontrado'], Response::HTTP_NOT_FOUND);
        }
        $terreno->delete();
        return response()->json(['success' => true, 'message' => 'Terreno eliminado']);
    }
}
