<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReproduccionAnimal;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ReproduccionAnimalController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = ReproduccionAnimal::with('animal', 'etapa');

        if ($request->has('animal_id')) {
            $query->forAnimal($request->animal_id);
        }
        if ($request->has('tipo')) {
            $query->byTipo($request->tipo);
        }
        if ($request->has('fecha_inicio')) {
            $query->byDateRange($request->fecha_inicio, $request->get('fecha_fin'));
        }

        if (!$user->isAdmin() && $user->isPropietario()) {
            $propietario = $user->propietario;
            if ($propietario) {
                $query->whereHas('animal.rebano.finca', function ($q) use ($propietario) {
                    $q->where('id_Propietario', $propietario->id);
                });
            }
        }

        $records = $query->paginate(15);

        return response()->json([
            'success'    => true,
            'message'    => 'Registros de reproducción',
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
            'repro_fecha_reproduccion' => 'required|date',
            'repro_tipo_reproduccion'  => 'nullable|string|max:8',
            'repro_observacion'        => 'nullable|string|max:60',
            'repro_etapa_anid'         => 'required|exists:animal,id_Animal',
            'repro_etapa_etid'         => 'required|exists:etapa,etapa_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $etapaAnimalExists = DB::table('etapa_animal')
            ->where('etan_animal_id', $request->repro_etapa_anid)
            ->where('etan_etapa_id', $request->repro_etapa_etid)
            ->exists();

        if (!$etapaAnimalExists) {
            return response()->json(['success' => false, 'message' => 'La relación etapa-animal no existe'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $repro = ReproduccionAnimal::create($request->only([
            'repro_fecha_reproduccion', 'repro_tipo_reproduccion',
            'repro_observacion', 'repro_etapa_anid', 'repro_etapa_etid',
        ]));

        return response()->json(['success' => true, 'message' => 'Reproducción registrada', 'data' => $repro->load('animal')], Response::HTTP_CREATED);
    }

    public function show($id)
    {
        $repro = ReproduccionAnimal::with('animal', 'etapa')->find($id);
        if (!$repro) {
            return response()->json(['success' => false, 'message' => 'Registro no encontrado'], Response::HTTP_NOT_FOUND);
        }
        return response()->json(['success' => true, 'data' => $repro]);
    }

    public function update(Request $request, $id)
    {
        $repro = ReproduccionAnimal::find($id);
        if (!$repro) {
            return response()->json(['success' => false, 'message' => 'Registro no encontrado'], Response::HTTP_NOT_FOUND);
        }

        $validator = Validator::make($request->all(), [
            'repro_fecha_reproduccion' => 'sometimes|date',
            'repro_tipo_reproduccion'  => 'nullable|string|max:8',
            'repro_observacion'        => 'nullable|string|max:60',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $repro->update($request->only(['repro_fecha_reproduccion', 'repro_tipo_reproduccion', 'repro_observacion']));

        return response()->json(['success' => true, 'message' => 'Reproducción actualizada', 'data' => $repro]);
    }

    public function destroy($id)
    {
        $repro = ReproduccionAnimal::find($id);
        if (!$repro) {
            return response()->json(['success' => false, 'message' => 'Registro no encontrado'], Response::HTTP_NOT_FOUND);
        }
        $repro->delete();
        return response()->json(['success' => true, 'message' => 'Registro eliminado']);
    }
}
