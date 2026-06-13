<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dosis;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DosisController extends Controller
{
    public function index(Request $request)
    {
        $query = Dosis::with(['vacuna', 'casaComercial', 'animal', 'rebano']);

        if ($request->filled('vacuna_id')) {
            $query->forVacuna((int) $request->vacuna_id);
        }

        if ($request->boolean('vigentes')) {
            $query->vigentes();
        }

        if ($request->filled('objetivo_tipo')) {
            $query->byObjetivoTipo($request->objetivo_tipo);
        }

        if ($request->filled('rebano_id')) {
            $query->where('dosis_objetivo_rebano_id', (int) $request->rebano_id);
        }

        $records = $query->orderByDesc('dosis_id')->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Dosis',
            'data' => $records->items(),
            'pagination' => [
                'current_page' => $records->currentPage(),
                'last_page' => $records->lastPage(),
                'per_page' => $records->perPage(),
                'total' => $records->total(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'dosis_vacuna_id' => 'required|exists:vacuna,vacuna_id',
            'dosis_casa_id' => 'required|exists:casa_comercial,casa_id',
            'dosis_objetivo_tipo' => ['required', Rule::in(['animal', 'rebano', 'subgrupo'])],
            'dosis_objetivo_animal_id' => 'nullable|exists:animal,id_Animal',
            'dosis_objetivo_rebano_id' => 'nullable|exists:rebano,id_Rebano',
            'dosis_objetivo_filtros' => 'nullable|array',
            'dosis_frecuencia' => 'required|integer|min:1',
            'dosis_costo' => 'nullable|numeric|min:0',
            'dosis_costo_frasco' => 'nullable|numeric|min:0',
            'dosis_fecha_uso_ini' => 'required|date',
            'dosis_fecha_uso_fin' => 'nullable|date|after_or_equal:dosis_fecha_uso_ini',
            'dosis_observacion' => 'nullable|string',
        ]);

        $validator->after(function ($validator) use ($request) {
            $tipo = $request->input('dosis_objetivo_tipo');

            if ($tipo === 'animal' && !$request->filled('dosis_objetivo_animal_id')) {
                $validator->errors()->add('dosis_objetivo_animal_id', 'El animal objetivo es requerido para dosis por animal.');
            }

            if (in_array($tipo, ['rebano', 'subgrupo'], true) && !$request->filled('dosis_objetivo_rebano_id')) {
                $validator->errors()->add('dosis_objetivo_rebano_id', 'El rebaño objetivo es requerido para dosis por rebaño/subgrupo.');
            }

            if ($tipo === 'subgrupo' && !$request->filled('dosis_objetivo_filtros')) {
                $validator->errors()->add('dosis_objetivo_filtros', 'Debe definir filtros para subgrupo.');
            }
        });

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $payload = $request->only([
            'dosis_vacuna_id',
            'dosis_casa_id',
            'dosis_objetivo_tipo',
            'dosis_objetivo_animal_id',
            'dosis_objetivo_rebano_id',
            'dosis_objetivo_filtros',
            'dosis_frecuencia',
            'dosis_costo',
            'dosis_costo_frasco',
            'dosis_fecha_uso_ini',
            'dosis_fecha_uso_fin',
            'dosis_observacion',
        ]);

        if ($payload['dosis_objetivo_tipo'] !== 'animal') {
            $payload['dosis_objetivo_animal_id'] = null;
        }

        if ($payload['dosis_objetivo_tipo'] === 'animal') {
            $payload['dosis_objetivo_rebano_id'] = null;
            $payload['dosis_objetivo_filtros'] = null;
        }

        if ($payload['dosis_objetivo_tipo'] === 'rebano') {
            $payload['dosis_objetivo_filtros'] = null;
        }

        $dosis = Dosis::create($payload);

        return response()->json([
            'success' => true,
            'message' => 'Dosis registrada',
            'data' => $dosis->load(['vacuna', 'casaComercial', 'animal', 'rebano']),
        ], Response::HTTP_CREATED);
    }

    public function show($id)
    {
        $dosis = Dosis::with(['vacuna', 'casaComercial', 'animal', 'rebano', 'historicoAplicaciones'])->find($id);

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
            'dosis_vacuna_id' => 'sometimes|exists:vacuna,vacuna_id',
            'dosis_casa_id' => 'sometimes|exists:casa_comercial,casa_id',
            'dosis_objetivo_tipo' => ['sometimes', Rule::in(['animal', 'rebano', 'subgrupo'])],
            'dosis_objetivo_animal_id' => 'nullable|exists:animal,id_Animal',
            'dosis_objetivo_rebano_id' => 'nullable|exists:rebano,id_Rebano',
            'dosis_objetivo_filtros' => 'nullable|array',
            'dosis_frecuencia' => 'sometimes|integer|min:1',
            'dosis_costo' => 'nullable|numeric|min:0',
            'dosis_costo_frasco' => 'nullable|numeric|min:0',
            'dosis_fecha_uso_ini' => 'sometimes|date',
            'dosis_fecha_uso_fin' => 'nullable|date|after_or_equal:dosis_fecha_uso_ini',
            'dosis_observacion' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $payload = $request->only([
            'dosis_vacuna_id',
            'dosis_casa_id',
            'dosis_objetivo_tipo',
            'dosis_objetivo_animal_id',
            'dosis_objetivo_rebano_id',
            'dosis_objetivo_filtros',
            'dosis_frecuencia',
            'dosis_costo',
            'dosis_costo_frasco',
            'dosis_fecha_uso_ini',
            'dosis_fecha_uso_fin',
            'dosis_observacion',
        ]);

        $tipo = $payload['dosis_objetivo_tipo'] ?? $dosis->dosis_objetivo_tipo;

        // Normalize objective fields exactly as store() does so hidden form fields
        // cannot keep stale target data when objective type changes.
        if ($tipo !== 'animal') {
            $payload['dosis_objetivo_animal_id'] = null;
        }

        if ($tipo === 'animal') {
            $payload['dosis_objetivo_rebano_id'] = null;
            $payload['dosis_objetivo_filtros'] = null;
        }

        if ($tipo === 'rebano') {
            $payload['dosis_objetivo_filtros'] = null;
        }

        $dosis->update($payload);

        return response()->json([
            'success' => true,
            'message' => 'Dosis actualizada',
            'data' => $dosis->load(['vacuna', 'casaComercial', 'animal', 'rebano']),
        ]);
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
