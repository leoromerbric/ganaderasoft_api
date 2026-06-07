<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Palpacion;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PalpacionController extends Controller
{
    public function index(Request $request)
    {
        $query = Palpacion::with('animal', 'etapa', 'tecnico');

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
            'message'    => 'Palpaciones',
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
            'id_Tecnico'          => 'nullable|exists:personal_finca,id_Personal',
            'palpacion_tipo'      => 'nullable|string|max:11',
            'palpacion_fecha'     => 'nullable|date',
            'palpacion_etapa_anid'=> 'required|exists:animal,id_Animal',
            'palpacion_etapa_etid'=> 'required|exists:etapa,etapa_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $etapaAnimalExists = DB::table('etapa_animal')
            ->where('etan_animal_id', $request->palpacion_etapa_anid)
            ->where('etan_etapa_id', $request->palpacion_etapa_etid)
            ->exists();

        if (!$etapaAnimalExists) {
            return response()->json(['success' => false, 'message' => 'La relación etapa-animal no existe'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $palpacion = Palpacion::create($request->only([
            'id_Tecnico', 'palpacion_tipo', 'palpacion_fecha',
            'palpacion_etapa_anid', 'palpacion_etapa_etid',
        ]));

        return response()->json(['success' => true, 'message' => 'Palpación registrada', 'data' => $palpacion->load('animal')], Response::HTTP_CREATED);
    }

    public function show($id)
    {
        $palpacion = Palpacion::with('animal', 'etapa', 'tecnico')->find($id);
        if (!$palpacion) {
            return response()->json(['success' => false, 'message' => 'Palpación no encontrada'], Response::HTTP_NOT_FOUND);
        }
        return response()->json(['success' => true, 'data' => $palpacion]);
    }

    public function update(Request $request, $id)
    {
        $palpacion = Palpacion::find($id);
        if (!$palpacion) {
            return response()->json(['success' => false, 'message' => 'Palpación no encontrada'], Response::HTTP_NOT_FOUND);
        }

        $validator = Validator::make($request->all(), [
            'id_Tecnico'     => 'nullable|exists:personal_finca,id_Personal',
            'palpacion_tipo' => 'nullable|string|max:11',
            'palpacion_fecha'=> 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $palpacion->update($request->only(['id_Tecnico', 'palpacion_tipo', 'palpacion_fecha']));

        return response()->json(['success' => true, 'message' => 'Palpación actualizada', 'data' => $palpacion]);
    }

    public function destroy($id)
    {
        $palpacion = Palpacion::find($id);
        if (!$palpacion) {
            return response()->json(['success' => false, 'message' => 'Palpación no encontrada'], Response::HTTP_NOT_FOUND);
        }
        $palpacion->delete();
        return response()->json(['success' => true, 'message' => 'Palpación eliminada']);
    }
}
