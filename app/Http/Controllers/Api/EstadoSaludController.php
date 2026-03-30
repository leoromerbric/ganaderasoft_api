<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EstadoSalud;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class EstadoSaludController extends Controller
{
    /**
     * Display a listing of estados de salud.
     */
    public function index(Request $request)
    {
        $query = EstadoSalud::query();
        
        // Apply search filter
        if ($request->has('search')) {
            $query->byName($request->search);
        }
        
        $estadosSalud = $query->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Lista de estados de salud',
            'data' => $estadosSalud
        ]);
    }

    /**
     * Store a newly created estado de salud.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'estado_nombre' => 'required|string|max:40|regex:/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ ]+$/'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = $request->user();
        
        // Only admin can create estados de salud
        if (!$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'No tiene permisos para crear estados de salud'
            ], Response::HTTP_FORBIDDEN);
        }

        $estadoSalud = EstadoSalud::create([
            'estado_nombre' => $request->estado_nombre
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Estado de salud creado exitosamente',
            'data' => $estadoSalud
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified estado de salud.
     */
    public function show(Request $request, $id)
    {
        $estadoSalud = EstadoSalud::with(['estadosAnimal.animal'])->find($id);

        if (!$estadoSalud) {
            return response()->json([
                'success' => false,
                'message' => 'Estado de salud no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detalle de estado de salud',
            'data' => $estadoSalud
        ]);
    }

    /**
     * Update the specified estado de salud.
     */
    public function update(Request $request, $id)
    {
        $estadoSalud = EstadoSalud::find($id);

        if (!$estadoSalud) {
            return response()->json([
                'success' => false,
                'message' => 'Estado de salud no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }

        $validator = Validator::make($request->all(), [
            'estado_nombre' => 'sometimes|string|max:40|regex:/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ ]+$/'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = $request->user();
        
        // Only admin can update estados de salud
        if (!$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'No tiene permisos para actualizar estados de salud'
            ], Response::HTTP_FORBIDDEN);
        }

        $estadoSalud->update($request->only(['estado_nombre']));

        return response()->json([
            'success' => true,
            'message' => 'Estado de salud actualizado exitosamente',
            'data' => $estadoSalud
        ]);
    }

    /**
     * Remove the specified estado de salud.
     */
    public function destroy(Request $request, $id)
    {
        $estadoSalud = EstadoSalud::find($id);

        if (!$estadoSalud) {
            return response()->json([
                'success' => false,
                'message' => 'Estado de salud no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }

        $user = $request->user();
        
        // Only admin can delete estados de salud
        if (!$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'No tiene permisos para eliminar estados de salud'
            ], Response::HTTP_FORBIDDEN);
        }

        // Check if the estado de salud is in use
        if ($estadoSalud->estadosAnimal()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar el estado de salud, está siendo utilizado'
            ], Response::HTTP_CONFLICT);
        }

        $estadoSalud->delete();

        return response()->json([
            'success' => true,
            'message' => 'Estado de salud eliminado exitosamente'
        ]);
    }
}