<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Diagnostico;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DiagnosticoController extends Controller
{
    public function index(Request $request)
    {
        $query = Diagnostico::with('animal', 'etapa', 'tratamientos');

        if ($request->has('animal_id')) {
            $query->forAnimal($request->animal_id);
        }
        if ($request->has('tipo')) {
            $query->byTipo($request->tipo);
        }
        if ($request->has('fecha_inicio')) {
            $query->byDateRange($request->fecha_inicio, $request->get('fecha_fin'));
        }

        $records = $query->paginate(15);

        return response()->json([
            'success'    => true,
            'message'    => 'Diagnósticos',
            'data'       => $records->items(),
            'pagination' => [
                'current_page' => $records->currentPage(),
                'last_page'    => $records->lastPage(),
                'per_page'     => $records->perPage(),
                'total'        => $records->total(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'diagnostico_descripcion'  => 'nullable|string',
            'diagnostico_tipo'         => 'nullable|string|max:30',
            'diagnostico_fecha'        => 'nullable|date',
            'fk_etapa_animal_anid'     => 'required|exists:animal,id_Animal',
            'fk_etapa_animal_etid'     => 'required|exists:etapa,etapa_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $etapaAnimalExists = DB::table('etapa_animal')
            ->where('etan_animal_id', $request->fk_etapa_animal_anid)
            ->where('etan_etapa_id', $request->fk_etapa_animal_etid)
            ->exists();

        if (!$etapaAnimalExists) {
            return response()->json(['success' => false, 'message' => 'La relación etapa-animal no existe'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $diagnostico = Diagnostico::create($request->only([
            'diagnostico_descripcion', 'diagnostico_tipo', 'diagnostico_fecha',
            'fk_etapa_animal_anid', 'fk_etapa_animal_etid',
        ]));

        return response()->json(['success' => true, 'message' => 'Diagnóstico registrado', 'data' => $diagnostico->load('animal')], Response::HTTP_CREATED);
    }

    public function show($id)
    {
        $diagnostico = Diagnostico::with('animal', 'etapa', 'tratamientos')->find($id);
        if (!$diagnostico) {
            return response()->json(['success' => false, 'message' => 'Diagnóstico no encontrado'], Response::HTTP_NOT_FOUND);
        }
        return response()->json(['success' => true, 'data' => $diagnostico]);
    }

    public function update(Request $request, $id)
    {
        $diagnostico = Diagnostico::find($id);
        if (!$diagnostico) {
            return response()->json(['success' => false, 'message' => 'Diagnóstico no encontrado'], Response::HTTP_NOT_FOUND);
        }

        $validator = Validator::make($request->all(), [
            'diagnostico_descripcion' => 'nullable|string',
            'diagnostico_tipo'        => 'nullable|string|max:30',
            'diagnostico_fecha'       => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $diagnostico->update($request->only(['diagnostico_descripcion', 'diagnostico_tipo', 'diagnostico_fecha']));

        return response()->json(['success' => true, 'message' => 'Diagnóstico actualizado', 'data' => $diagnostico]);
    }

    public function destroy($id)
    {
        $diagnostico = Diagnostico::find($id);
        if (!$diagnostico) {
            return response()->json(['success' => false, 'message' => 'Diagnóstico no encontrado'], Response::HTTP_NOT_FOUND);
        }
        $diagnostico->delete();
        return response()->json(['success' => true, 'message' => 'Diagnóstico eliminado']);
    }
}
