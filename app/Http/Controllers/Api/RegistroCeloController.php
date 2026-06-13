<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RegistroCelo;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RegistroCeloController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = RegistroCelo::with('animal', 'etapa');

        if ($request->has('animal_id')) {
            $query->forAnimal($request->animal_id);
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
            'message'    => 'Registros de celo',
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
            'celo_fecha'       => 'required|date',
            'celo_observacon'  => 'nullable|string|max:100',
            'celo_etapa_anid'  => 'required|exists:animal,id_Animal',
            'celo_etapa_etid'  => 'required|exists:etapa,etapa_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $etapaAnimalExists = DB::table('etapa_animal')
            ->where('etan_animal_id', $request->celo_etapa_anid)
            ->where('etan_etapa_id', $request->celo_etapa_etid)
            ->exists();

        if (!$etapaAnimalExists) {
            return response()->json(['success' => false, 'message' => 'La relación etapa-animal no existe'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $celo = RegistroCelo::create($request->only(['celo_fecha', 'celo_observacon', 'celo_etapa_anid', 'celo_etapa_etid']));

        return response()->json(['success' => true, 'message' => 'Registro de celo creado', 'data' => $celo], Response::HTTP_CREATED);
    }

    public function show($id)
    {
        $celo = RegistroCelo::with('animal', 'etapa', 'servicios')->find($id);
        if (!$celo) {
            return response()->json(['success' => false, 'message' => 'Registro no encontrado'], Response::HTTP_NOT_FOUND);
        }
        return response()->json(['success' => true, 'data' => $celo]);
    }

    public function update(Request $request, $id)
    {
        $celo = RegistroCelo::find($id);
        if (!$celo) {
            return response()->json(['success' => false, 'message' => 'Registro no encontrado'], Response::HTTP_NOT_FOUND);
        }

        $validator = Validator::make($request->all(), [
            'celo_fecha'      => 'sometimes|date',
            'celo_observacon' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $celo->update($request->only(['celo_fecha', 'celo_observacon']));

        return response()->json(['success' => true, 'message' => 'Registro de celo actualizado', 'data' => $celo]);
    }

    public function destroy($id)
    {
        $celo = RegistroCelo::find($id);
        if (!$celo) {
            return response()->json(['success' => false, 'message' => 'Registro no encontrado'], Response::HTTP_NOT_FOUND);
        }
        $celo->delete();
        return response()->json(['success' => true, 'message' => 'Registro de celo eliminado']);
    }
}
