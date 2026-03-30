<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Etapa;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class EtapaController extends Controller
{
    /**
     * Display a listing of etapas.
     */
    public function index(Request $request)
    {
        $query = Etapa::with(['tipoAnimal']);

        // Apply filters
        if ($request->has('tipo_animal_id')) {
            $query->forTipoAnimal($request->tipo_animal_id);
        }

        if ($request->has('sexo')) {
            $query->bySexo($request->sexo);
        }

        if ($request->has('nombre')) {
            $query->byName($request->nombre);
        }

        $etapas = $query->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Lista de etapas obtenida exitosamente',
            'data' => $etapas->items(),
            'pagination' => [
                'current_page' => $etapas->currentPage(),
                'last_page' => $etapas->lastPage(),
                'per_page' => $etapas->perPage(),
                'total' => $etapas->total(),
            ]
        ], Response::HTTP_OK);
    }

    /**
     * Store a newly created etapa.
     */
    public function store(Request $request)
    {
        // Only admin can create etapas
        $user = $request->user();
        if (!$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'No tiene permisos para crear etapas'
            ], Response::HTTP_FORBIDDEN);
        }

        $validator = Validator::make($request->all(), [
            'etapa_nombre' => 'required|string|max:40',
            'etapa_edad_ini' => 'required|integer|min:0',
            'etapa_edad_fin' => 'nullable|integer|min:0|gt:etapa_edad_ini',
            'etapa_fk_tipo_animal_id' => 'required|exists:tipo_animal,tipo_animal_id',
            'etapa_sexo' => 'required|in:M,F',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $etapa = Etapa::create($request->all());
        $etapa->load(['tipoAnimal']);

        return response()->json([
            'success' => true,
            'message' => 'Etapa creada exitosamente',
            'data' => $etapa
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified etapa.
     */
    public function show($id)
    {
        $etapa = Etapa::with(['tipoAnimal', 'etapaAnimales.animal'])->find($id);

        if (!$etapa) {
            return response()->json([
                'success' => false,
                'message' => 'Etapa no encontrada'
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success' => true,
            'message' => 'Etapa obtenida exitosamente',
            'data' => $etapa
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified etapa.
     */
    public function update(Request $request, $id)
    {
        // Only admin can update etapas
        $user = $request->user();
        if (!$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'No tiene permisos para actualizar etapas'
            ], Response::HTTP_FORBIDDEN);
        }

        $etapa = Etapa::find($id);

        if (!$etapa) {
            return response()->json([
                'success' => false,
                'message' => 'Etapa no encontrada'
            ], Response::HTTP_NOT_FOUND);
        }

        $validator = Validator::make($request->all(), [
            'etapa_nombre' => 'sometimes|string|max:40',
            'etapa_edad_ini' => 'sometimes|integer|min:0',
            'etapa_edad_fin' => 'nullable|integer|min:0|gt:etapa_edad_ini',
            'etapa_fk_tipo_animal_id' => 'sometimes|exists:tipo_animal,tipo_animal_id',
            'etapa_sexo' => 'sometimes|in:M,F',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $etapa->update($request->all());
        $etapa->load(['tipoAnimal']);

        return response()->json([
            'success' => true,
            'message' => 'Etapa actualizada exitosamente',
            'data' => $etapa
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified etapa.
     */
    public function destroy(Request $request, $id)
    {
        // Only admin can delete etapas
        $user = $request->user();
        if (!$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'No tiene permisos para eliminar etapas'
            ], Response::HTTP_FORBIDDEN);
        }

        $etapa = Etapa::find($id);

        if (!$etapa) {
            return response()->json([
                'success' => false,
                'message' => 'Etapa no encontrada'
            ], Response::HTTP_NOT_FOUND);
        }

        // Check if there are etapa animal records using this etapa
        if ($etapa->etapaAnimales()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar la etapa porque tiene registros de etapa animal asociados'
            ], Response::HTTP_CONFLICT);
        }

        $etapa->delete();

        return response()->json([
            'success' => true,
            'message' => 'Etapa eliminada exitosamente'
        ], Response::HTTP_OK);
    }
}