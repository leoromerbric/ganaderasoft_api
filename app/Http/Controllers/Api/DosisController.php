<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dosis;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class DosisController extends Controller
{
    public function index(Request $request)
    {
        $query = Dosis::with('vacuna', 'casaComercial');

        if ($request->has('vacuna_id')) {
            $query->forVacuna($request->vacuna_id);
        }
        if ($request->has('vigentes') && $request->vigentes) {
            $query->vigentes();
        }

        $records = $query->paginate(15);

        return response()->json([
            'success'    => true,
            'message'    => 'Dosis',
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
            'dosis_vacuna_id'           => 'required|exists:vacuna,vacuna_id',
            'dosis_casa_id'             => 'required|exists:casa_comercial,casa_id',
            'dosis_frecuencia'          => 'required|integer|min:1',
            'dosis_costo'               => 'nullable|numeric|min:0',
            'dosis_costo_frasco'        => 'nullable|numeric|min:0',
            'dosis_fecha_uso_ini'       => 'required|date',
            'dosis_fecha_uso_fin'       => 'nullable|date|after:dosis_fecha_uso_ini',
            'dosis_etapa_animal_anid'   => 'required|exists:animal,id_Animal',
            'dosis_etapa_animal_etid'   => 'required|exists:etapa,etapa_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $dosis = Dosis::create($request->only([
            'dosis_vacuna_id', 'dosis_casa_id', 'dosis_frecuencia', 'dosis_costo',
            'dosis_costo_frasco', 'dosis_fecha_uso_ini', 'dosis_fecha_uso_fin',
            'dosis_etapa_animal_anid', 'dosis_etapa_animal_etid',
        ]));

        return response()->json(['success' => true, 'message' => 'Dosis registrada', 'data' => $dosis->load('vacuna', 'casaComercial')], Response::HTTP_CREATED);
    }

    public function show($id)
    {
        $dosis = Dosis::with('vacuna', 'casaComercial', 'historicoAplicaciones')->find($id);
        if (!$dosis) {
            return response()->json(['success' => false, 'message' => 'Dosis no encontrada'], Response::HTTP_NOT_FOUND);
        }
        return response()->json(['success' => true, 'data' => $dosis]);
    }

    public function update(Request $request, $id)
    {
        $dosis = Dosis::find($id);
        if (!$dosis) {
            return response()->json(['success' => false, 'message' => 'Dosis no encontrada'], Response::HTTP_NOT_FOUND);
        }

        $validator = Validator::make($request->all(), [
            'dosis_frecuencia'   => 'sometimes|integer|min:1',
            'dosis_costo'        => 'nullable|numeric|min:0',
            'dosis_costo_frasco' => 'nullable|numeric|min:0',
            'dosis_fecha_uso_ini'=> 'sometimes|date',
            'dosis_fecha_uso_fin'=> 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $dosis->update($request->only([
            'dosis_frecuencia', 'dosis_costo', 'dosis_costo_frasco',
            'dosis_fecha_uso_ini', 'dosis_fecha_uso_fin',
        ]));

        return response()->json(['success' => true, 'message' => 'Dosis actualizada', 'data' => $dosis]);
    }

    public function destroy($id)
    {
        $dosis = Dosis::find($id);
        if (!$dosis) {
            return response()->json(['success' => false, 'message' => 'Dosis no encontrada'], Response::HTTP_NOT_FOUND);
        }
        $dosis->delete();
        return response()->json(['success' => true, 'message' => 'Dosis eliminada']);
    }
}
