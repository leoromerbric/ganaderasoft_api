<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TipoAnimal;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class TipoAnimalController extends Controller
{
    /**
     * Display a listing of tipos de animal.
     */
    public function index(Request $request)
    {
        $query = TipoAnimal::query();
        
        // Apply search filter
        if ($request->has('search')) {
            $query->byName($request->search);
        }
        
        $tiposAnimal = $query->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Lista de tipos de animal',
            'data' => $tiposAnimal
        ]);
    }

    /**
     * Store a newly created tipo de animal.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tipo_animal_nombre' => 'required|string|max:40|regex:/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ ]+$/'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = $request->user();
        
        // Only admin can create tipos de animal
        if (!$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'No tiene permisos para crear tipos de animal'
            ], Response::HTTP_FORBIDDEN);
        }

        $tipoAnimal = TipoAnimal::create([
            'tipo_animal_nombre' => $request->tipo_animal_nombre
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tipo de animal creado exitosamente',
            'data' => $tipoAnimal
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified tipo de animal.
     */
    public function show(Request $request, $id)
    {
        $tipoAnimal = TipoAnimal::find($id);

        if (!$tipoAnimal) {
            return response()->json([
                'success' => false,
                'message' => 'Tipo de animal no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detalle de tipo de animal',
            'data' => $tipoAnimal
        ]);
    }

    /**
     * Update the specified tipo de animal.
     */
    public function update(Request $request, $id)
    {
        $tipoAnimal = TipoAnimal::find($id);

        if (!$tipoAnimal) {
            return response()->json([
                'success' => false,
                'message' => 'Tipo de animal no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }

        $validator = Validator::make($request->all(), [
            'tipo_animal_nombre' => 'sometimes|string|max:40|regex:/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ ]+$/'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = $request->user();
        
        // Only admin can update tipos de animal
        if (!$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'No tiene permisos para actualizar tipos de animal'
            ], Response::HTTP_FORBIDDEN);
        }

        $tipoAnimal->update($request->only(['tipo_animal_nombre']));

        return response()->json([
            'success' => true,
            'message' => 'Tipo de animal actualizado exitosamente',
            'data' => $tipoAnimal
        ]);
    }

    /**
     * Remove the specified tipo de animal.
     */
    public function destroy(Request $request, $id)
    {
        $tipoAnimal = TipoAnimal::find($id);

        if (!$tipoAnimal) {
            return response()->json([
                'success' => false,
                'message' => 'Tipo de animal no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }

        $user = $request->user();
        
        // Only admin can delete tipos de animal
        if (!$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'No tiene permisos para eliminar tipos de animal'
            ], Response::HTTP_FORBIDDEN);
        }

        $tipoAnimal->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tipo de animal eliminado exitosamente'
        ]);
    }
}