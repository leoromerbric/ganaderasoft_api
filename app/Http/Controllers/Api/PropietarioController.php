<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Propietario;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class PropietarioController extends Controller
{
    /**
     * Display a listing of propietarios.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // If user is admin, show all propietarios
        if ($user->isAdmin()) {
            $propietarios = Propietario::with(['user', 'fincas'])
                ->active()
                ->paginate(15);
        } else {
            // If user is propietario, show only their own record
            $propietario = $user->propietario;
            if (!$propietario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no es propietario'
                ], Response::HTTP_FORBIDDEN);
            }
            
            $propietarios = Propietario::with(['user', 'fincas'])
                ->where('id', $propietario->id)
                ->active()
                ->paginate(15);
        }

        return response()->json([
            'success' => true,
            'message' => 'Lista de propietarios',
            'data' => $propietarios
        ]);
    }

    /**
     * Store a newly created propietario.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:users,id',
            'Nombre' => 'required|string|max:255',
            'Apellido' => 'required|string|max:255',
            'Telefono' => 'nullable|string|max:20',
            'id_Personal' => 'nullable|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = $request->user();
        
        // Only admin can create propietarios for other users
        if (!$user->isAdmin() && $request->id != $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'No tiene permisos para crear propietario para otro usuario'
            ], Response::HTTP_FORBIDDEN);
        }

        // Check if propietario already exists for this user
        $existingPropietario = Propietario::where('id', $request->id)->first();
        if ($existingPropietario) {
            return response()->json([
                'success' => false,
                'message' => 'Ya existe un propietario para este usuario'
            ], Response::HTTP_CONFLICT);
        }

        $propietario = Propietario::create([
            'id' => $request->id,
            'id_Personal' => $request->id_Personal,
            'Nombre' => $request->Nombre,
            'Apellido' => $request->Apellido,
            'Telefono' => $request->Telefono,
            'archivado' => false
        ]);

        $propietario->load(['user', 'fincas']);

        return response()->json([
            'success' => true,
            'message' => 'Propietario creado exitosamente',
            'data' => $propietario
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified propietario.
     */
    public function show(Request $request, $id)
    {
        $propietario = Propietario::with(['user', 'fincas'])->find($id);

        if (!$propietario) {
            return response()->json([
                'success' => false,
                'message' => 'Propietario no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }

        $user = $request->user();
        
        // Check permissions: admin can see all, propietario only their own
        if (!$user->isAdmin() && $user->id != $propietario->id) {
            return response()->json([
                'success' => false,
                'message' => 'No tiene permisos para ver este propietario'
            ], Response::HTTP_FORBIDDEN);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detalle de propietario',
            'data' => $propietario
        ]);
    }

    /**
     * Update the specified propietario.
     */
    public function update(Request $request, $id)
    {
        $propietario = Propietario::find($id);

        if (!$propietario) {
            return response()->json([
                'success' => false,
                'message' => 'Propietario no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }

        $validator = Validator::make($request->all(), [
            'Nombre' => 'sometimes|string|max:255',
            'Apellido' => 'sometimes|string|max:255',
            'Telefono' => 'nullable|string|max:20',
            'id_Personal' => 'nullable|integer'
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
        if (!$user->isAdmin() && $user->id != $propietario->id) {
            return response()->json([
                'success' => false,
                'message' => 'No tiene permisos para actualizar este propietario'
            ], Response::HTTP_FORBIDDEN);
        }

        $propietario->update($request->only(['Nombre', 'Apellido', 'Telefono', 'id_Personal']));
        $propietario->load(['user', 'fincas']);

        return response()->json([
            'success' => true,
            'message' => 'Propietario actualizado exitosamente',
            'data' => $propietario
        ]);
    }

    /**
     * Remove the specified propietario (soft delete).
     */
    public function destroy(Request $request, $id)
    {
        $propietario = Propietario::find($id);

        if (!$propietario) {
            return response()->json([
                'success' => false,
                'message' => 'Propietario no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }

        $user = $request->user();
        
        // Only admin can delete propietarios
        if (!$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'No tiene permisos para eliminar propietarios'
            ], Response::HTTP_FORBIDDEN);
        }

        // Soft delete by setting archivado = true
        $propietario->update(['archivado' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Propietario eliminado exitosamente'
        ]);
    }
}