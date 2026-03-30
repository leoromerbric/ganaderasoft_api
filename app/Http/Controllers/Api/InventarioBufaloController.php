<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventarioBufalo;
use App\Models\Finca;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class InventarioBufaloController extends Controller
{
    /**
     * Display a listing of inventario bufalo.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Build query with filters
        $query = InventarioBufalo::with(['finca.propietario'])
            ->recent();
            
        // Apply filters
        if ($request->has('finca_id')) {
            $query->forFinca($request->finca_id);
        }
        
        // If user is admin, show all inventarios
        if ($user->isAdmin()) {
            $inventarios = $query->paginate(15);
        } else {
            // If user is propietario, show only inventarios from their fincas
            $propietario = $user->propietario;
            if (!$propietario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no es propietario'
                ], Response::HTTP_FORBIDDEN);
            }
            
            $inventarios = $query->whereHas('finca', function ($q) use ($propietario) {
                $q->where('id_Propietario', $propietario->id);
            })->paginate(15);
        }

        return response()->json([
            'success' => true,
            'message' => 'Lista de inventarios de búfalo',
            'data' => $inventarios
        ]);
    }

    /**
     * Store a newly created inventario bufalo.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_Finca' => 'required|exists:finca,id_Finca',
            'Num_Becerro' => 'nullable|integer|min:0',
            'Num_Anojo' => 'nullable|integer|min:0',
            'Num_Bubilla' => 'nullable|integer|min:0',
            'Num_Bufalo' => 'nullable|integer|min:0',
            'Fecha_Inventario' => 'required|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = $request->user();
        
        // Check permissions: admin can create for any finca, propietario only for their fincas
        if (!$user->isAdmin()) {
            $propietario = $user->propietario;
            if (!$propietario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no es propietario'
                ], Response::HTTP_FORBIDDEN);
            }
            
            $finca = Finca::find($request->id_Finca);
            if (!$finca || $finca->id_Propietario != $propietario->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para crear inventario en esta finca'
                ], Response::HTTP_FORBIDDEN);
            }
        }

        $inventario = InventarioBufalo::create([
            'id_Finca' => $request->id_Finca,
            'Num_Becerro' => $request->Num_Becerro ?? 0,
            'Num_Anojo' => $request->Num_Anojo ?? 0,
            'Num_Bubilla' => $request->Num_Bubilla ?? 0,
            'Num_Bufalo' => $request->Num_Bufalo ?? 0,
            'Fecha_Inventario' => $request->Fecha_Inventario
        ]);

        $inventario->load(['finca.propietario']);

        return response()->json([
            'success' => true,
            'message' => 'Inventario de búfalo creado exitosamente',
            'data' => $inventario
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified inventario bufalo.
     */
    public function show(Request $request, $id)
    {
        $inventario = InventarioBufalo::with(['finca.propietario'])->find($id);

        if (!$inventario) {
            return response()->json([
                'success' => false,
                'message' => 'Inventario de búfalo no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }

        $user = $request->user();
        
        // Check permissions: admin can see all, propietario only their own
        if (!$user->isAdmin()) {
            $propietario = $user->propietario;
            if (!$propietario || $inventario->finca->id_Propietario != $propietario->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para ver este inventario'
                ], Response::HTTP_FORBIDDEN);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Detalle de inventario de búfalo',
            'data' => $inventario
        ]);
    }

    /**
     * Update the specified inventario bufalo.
     */
    public function update(Request $request, $id)
    {
        $inventario = InventarioBufalo::find($id);

        if (!$inventario) {
            return response()->json([
                'success' => false,
                'message' => 'Inventario de búfalo no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }

        $validator = Validator::make($request->all(), [
            'Num_Becerro' => 'nullable|integer|min:0',
            'Num_Anojo' => 'nullable|integer|min:0',
            'Num_Bubilla' => 'nullable|integer|min:0',
            'Num_Bufalo' => 'nullable|integer|min:0',
            'Fecha_Inventario' => 'sometimes|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = $request->user();
        
        // Check permissions: admin can update all, propietario only their own
        if (!$user->isAdmin()) {
            $propietario = $user->propietario;
            if (!$propietario || $inventario->finca->id_Propietario != $propietario->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para actualizar este inventario'
                ], Response::HTTP_FORBIDDEN);
            }
        }

        $inventario->update($request->only([
            'Num_Becerro', 'Num_Anojo', 'Num_Bubilla', 'Num_Bufalo', 'Fecha_Inventario'
        ]));
        
        $inventario->load(['finca.propietario']);

        return response()->json([
            'success' => true,
            'message' => 'Inventario de búfalo actualizado exitosamente',
            'data' => $inventario
        ]);
    }

    /**
     * Remove the specified inventario bufalo.
     */
    public function destroy(Request $request, $id)
    {
        $inventario = InventarioBufalo::find($id);

        if (!$inventario) {
            return response()->json([
                'success' => false,
                'message' => 'Inventario de búfalo no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }

        $user = $request->user();
        
        // Check permissions: admin can delete all, propietario only their own
        if (!$user->isAdmin()) {
            $propietario = $user->propietario;
            if (!$propietario || $inventario->finca->id_Propietario != $propietario->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para eliminar este inventario'
                ], Response::HTTP_FORBIDDEN);
            }
        }

        // Hard delete for inventario records
        $inventario->delete();

        return response()->json([
            'success' => true,
            'message' => 'Inventario de búfalo eliminado exitosamente'
        ]);
    }
}