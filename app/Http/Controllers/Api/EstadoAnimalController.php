<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EstadoAnimal;
use App\Models\Animal;
use App\Models\EstadoSalud;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class EstadoAnimalController extends Controller
{
    /**
     * Display a listing of estados de animal.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Build query with filters
        $query = EstadoAnimal::with(['animal.rebano.finca', 'estadoSalud']);
            
        // Apply filters
        if ($request->has('animal_id')) {
            $query->forAnimal($request->animal_id);
        }
        
        if ($request->has('estado_id')) {
            $query->byEstado($request->estado_id);
        }
        
        if ($request->has('active') && $request->active == 'true') {
            $query->active();
        }
        
        // If user is admin, show all estados
        if ($user->isAdmin()) {
            $estadosAnimal = $query->paginate(15);
        } else {
            // If user is propietario, show only estados from their animals
            $propietario = $user->propietario;
            if (!$propietario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no es propietario'
                ], Response::HTTP_FORBIDDEN);
            }
            
            $estadosAnimal = $query->whereHas('animal.rebano.finca', function ($q) use ($propietario) {
                $q->where('id_Propietario', $propietario->id);
            })->paginate(15);
        }

        return response()->json([
            'success' => true,
            'message' => 'Lista de estados de animales',
            'data' => $estadosAnimal
        ]);
    }

    /**
     * Store a newly created estado animal.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'esan_fecha_ini' => 'required|date',
            'esan_fecha_fin' => 'nullable|date|after_or_equal:esan_fecha_ini',
            'esan_fk_estado_id' => 'required|exists:estado_salud,estado_id',
            'esan_fk_id_animal' => 'required|exists:animal,id_Animal'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = $request->user();
        
        // Check permissions: admin can create for any animal, propietario only for their animals
        if (!$user->isAdmin()) {
            $propietario = $user->propietario;
            if (!$propietario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no es propietario'
                ], Response::HTTP_FORBIDDEN);
            }
            
            $animal = Animal::with('rebano.finca')->find($request->esan_fk_id_animal);
            if (!$animal || $animal->rebano->finca->id_Propietario != $propietario->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para crear estado para este animal'
                ], Response::HTTP_FORBIDDEN);
            }
        }

        $estadoAnimal = EstadoAnimal::create([
            'esan_fecha_ini' => $request->esan_fecha_ini,
            'esan_fecha_fin' => $request->esan_fecha_fin,
            'esan_fk_estado_id' => $request->esan_fk_estado_id,
            'esan_fk_id_animal' => $request->esan_fk_id_animal
        ]);

        $estadoAnimal->load(['animal.rebano.finca', 'estadoSalud']);

        return response()->json([
            'success' => true,
            'message' => 'Estado de animal creado exitosamente',
            'data' => $estadoAnimal
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified estado animal.
     */
    public function show(Request $request, $id)
    {
        $estadoAnimal = EstadoAnimal::with(['animal.rebano.finca.propietario', 'estadoSalud'])->find($id);

        if (!$estadoAnimal) {
            return response()->json([
                'success' => false,
                'message' => 'Estado de animal no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }

        $user = $request->user();
        
        // Check permissions: admin can see all, propietario only their own
        if (!$user->isAdmin()) {
            $propietario = $user->propietario;
            if (!$propietario || $estadoAnimal->animal->rebano->finca->id_Propietario != $propietario->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para ver este estado de animal'
                ], Response::HTTP_FORBIDDEN);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Detalle de estado de animal',
            'data' => $estadoAnimal
        ]);
    }

    /**
     * Update the specified estado animal.
     */
    public function update(Request $request, $id)
    {
        $estadoAnimal = EstadoAnimal::find($id);

        if (!$estadoAnimal) {
            return response()->json([
                'success' => false,
                'message' => 'Estado de animal no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }

        $validator = Validator::make($request->all(), [
            'esan_fecha_ini' => 'sometimes|date',
            'esan_fecha_fin' => 'nullable|date|after_or_equal:esan_fecha_ini',
            'esan_fk_estado_id' => 'sometimes|exists:estado_salud,estado_id',
            'esan_fk_id_animal' => 'sometimes|exists:animal,id_Animal'
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
            if (!$propietario || $estadoAnimal->animal->rebano->finca->id_Propietario != $propietario->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para actualizar este estado de animal'
                ], Response::HTTP_FORBIDDEN);
            }
            
            // If changing animal, check permissions on new animal
            if ($request->has('esan_fk_id_animal')) {
                $newAnimal = Animal::with('rebano.finca')->find($request->esan_fk_id_animal);
                if (!$newAnimal || $newAnimal->rebano->finca->id_Propietario != $propietario->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tiene permisos para asignar estado a ese animal'
                    ], Response::HTTP_FORBIDDEN);
                }
            }
        }

        $estadoAnimal->update($request->only([
            'esan_fecha_ini', 'esan_fecha_fin', 'esan_fk_estado_id', 'esan_fk_id_animal'
        ]));
        
        $estadoAnimal->load(['animal.rebano.finca', 'estadoSalud']);

        return response()->json([
            'success' => true,
            'message' => 'Estado de animal actualizado exitosamente',
            'data' => $estadoAnimal
        ]);
    }

    /**
     * Remove the specified estado animal.
     */
    public function destroy(Request $request, $id)
    {
        $estadoAnimal = EstadoAnimal::find($id);

        if (!$estadoAnimal) {
            return response()->json([
                'success' => false,
                'message' => 'Estado de animal no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }

        $user = $request->user();
        
        // Check permissions: admin can delete all, propietario only their own
        if (!$user->isAdmin()) {
            $propietario = $user->propietario;
            if (!$propietario || $estadoAnimal->animal->rebano->finca->id_Propietario != $propietario->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para eliminar este estado de animal'
                ], Response::HTTP_FORBIDDEN);
            }
        }

        $estadoAnimal->delete();

        return response()->json([
            'success' => true,
            'message' => 'Estado de animal eliminado exitosamente'
        ]);
    }
}