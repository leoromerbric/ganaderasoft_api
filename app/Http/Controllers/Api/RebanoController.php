<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rebano;
use App\Models\Finca;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class RebanoController extends Controller
{
    /**
     * Display a listing of rebanos.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // If user is admin, show all rebanos
        if ($user->isAdmin()) {
            $rebanos = Rebano::with(['finca.propietario', 'animales'])
                ->active()
                ->paginate(15);
        } else {
            // If user is propietario, show only rebanos from their fincas
            $propietario = $user->propietario;
            if (!$propietario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no es propietario'
                ], Response::HTTP_FORBIDDEN);
            }
            
            $rebanos = Rebano::with(['finca.propietario', 'animales'])
                ->whereHas('finca', function ($query) use ($propietario) {
                    $query->where('id_Propietario', $propietario->id);
                })
                ->active()
                ->paginate(15);
        }

        return response()->json([
            'success' => true,
            'message' => 'Lista de rebaños',
            'data' => $rebanos
        ]);
    }

    /**
     * Store a newly created rebano.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_Finca' => 'required|exists:finca,id_Finca',
            'Nombre' => 'required|string|max:25'
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
                    'message' => 'No tiene permisos para crear rebaño en esta finca'
                ], Response::HTTP_FORBIDDEN);
            }
        }

        $rebano = Rebano::create([
            'id_Finca' => $request->id_Finca,
            'Nombre' => $request->Nombre,
            'archivado' => false
        ]);

        $rebano->load(['finca.propietario', 'animales']);

        return response()->json([
            'success' => true,
            'message' => 'Rebaño creado exitosamente',
            'data' => $rebano
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified rebano.
     */
    public function show(Request $request, $id)
    {
        $rebano = Rebano::with(['finca.propietario', 'animales'])->find($id);

        if (!$rebano) {
            return response()->json([
                'success' => false,
                'message' => 'Rebaño no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }

        $user = $request->user();
        
        // Check permissions: admin can see all, propietario only their own
        if (!$user->isAdmin()) {
            $propietario = $user->propietario;
            if (!$propietario || $rebano->finca->id_Propietario != $propietario->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para ver este rebaño'
                ], Response::HTTP_FORBIDDEN);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Detalle de rebaño',
            'data' => $rebano
        ]);
    }

    /**
     * Update the specified rebano.
     */
    public function update(Request $request, $id)
    {
        $rebano = Rebano::find($id);

        if (!$rebano) {
            return response()->json([
                'success' => false,
                'message' => 'Rebaño no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }

        $validator = Validator::make($request->all(), [
            'Nombre' => 'sometimes|string|max:25',
            'id_Finca' => 'sometimes|exists:finca,id_Finca'
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
            if (!$propietario || $rebano->finca->id_Propietario != $propietario->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para actualizar este rebaño'
                ], Response::HTTP_FORBIDDEN);
            }
            
            // If changing finca, check permissions on new finca
            if ($request->has('id_Finca')) {
                $newFinca = Finca::find($request->id_Finca);
                if (!$newFinca || $newFinca->id_Propietario != $propietario->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tiene permisos para mover el rebaño a esa finca'
                    ], Response::HTTP_FORBIDDEN);
                }
            }
        }

        $rebano->update($request->only(['Nombre', 'id_Finca']));
        $rebano->load(['finca.propietario', 'animales']);

        return response()->json([
            'success' => true,
            'message' => 'Rebaño actualizado exitosamente',
            'data' => $rebano
        ]);
    }

    /**
     * Remove the specified rebano (soft delete).
     */
    public function destroy(Request $request, $id)
    {
        $rebano = Rebano::find($id);

        if (!$rebano) {
            return response()->json([
                'success' => false,
                'message' => 'Rebaño no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }

        $user = $request->user();
        
        // Check permissions: admin can delete all, propietario only their own
        if (!$user->isAdmin()) {
            $propietario = $user->propietario;
            if (!$propietario || $rebano->finca->id_Propietario != $propietario->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para eliminar este rebaño'
                ], Response::HTTP_FORBIDDEN);
            }
        }

        // Check if rebano has animals
        if ($rebano->animales()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar el rebaño, tiene animales asociados'
            ], Response::HTTP_CONFLICT);
        }

        // Soft delete by setting archivado = true
        $rebano->update(['archivado' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Rebaño eliminado exitosamente'
        ]);
    }
}