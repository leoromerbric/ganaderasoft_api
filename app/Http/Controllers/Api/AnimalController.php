<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Animal;
use App\Models\Rebano;
use App\Models\EstadoAnimal;
use App\Models\EtapaAnimal;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class AnimalController extends Controller
{
    /**
     * Display a listing of animals.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Build query with filters
        $query = Animal::with(['rebano.finca.propietario', 'composicionRaza'])
            ->active();
            
        // Apply filters
        if ($request->has('rebano_id')) {
            $query->forRebano($request->rebano_id);
        }
        
        if ($request->has('sexo')) {
            $query->bySex($request->sexo);
        }
        
        // If user is admin, show all animals
        if ($user->isAdmin()) {
            $animals = $query->paginate(15);
        } else {
            // If user is propietario, show only animals from their fincas
            $propietario = $user->propietario;
            if (!$propietario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no es propietario'
                ], Response::HTTP_FORBIDDEN);
            }
            
            $animals = $query->whereHas('rebano.finca', function ($q) use ($propietario) {
                $q->where('id_Propietario', $propietario->id);
            })->paginate(15);
        }

        return response()->json([
            'success' => true,
            'message' => 'Lista de animales',
            'data' => $animals
        ]);
    }

    /**
     * Store a newly created animal.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_Rebano' => 'required|exists:rebano,id_Rebano',
            'Nombre' => 'nullable|string|max:25',
            'codigo_animal' => 'nullable|string|max:20|unique:animal,codigo_animal',
            'Sexo' => 'required|in:M,F',
            'fecha_nacimiento' => 'required|date',
            'Procedencia' => 'nullable|string|max:50',
            'fk_composicion_raza' => 'required|integer',
            'estado_inicial' => 'nullable|array',
            'estado_inicial.estado_id' => 'required_with:estado_inicial|exists:estado_salud,estado_id',
            'estado_inicial.fecha_ini' => 'required_with:estado_inicial|date',
            'etapa_inicial' => 'nullable|array',
            'etapa_inicial.etapa_id' => 'required_with:etapa_inicial|exists:etapa,etapa_id',
            'etapa_inicial.fecha_ini' => 'required_with:etapa_inicial|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = $request->user();
        
        // Check permissions: admin can create for any rebano, propietario only for their rebanos
        if (!$user->isAdmin()) {
            $propietario = $user->propietario;
            if (!$propietario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no es propietario'
                ], Response::HTTP_FORBIDDEN);
            }
            
            $rebano = Rebano::with('finca')->find($request->id_Rebano);
            if (!$rebano || $rebano->finca->id_Propietario != $propietario->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para crear animal en este rebaño'
                ], Response::HTTP_FORBIDDEN);
            }
        }

        $animal = Animal::create([
            'id_Rebano' => $request->id_Rebano,
            'Nombre' => $request->Nombre,
            'codigo_animal' => $request->codigo_animal,
            'Sexo' => $request->Sexo,
            'fecha_nacimiento' => $request->fecha_nacimiento,
            'Procedencia' => $request->Procedencia,
            'fk_composicion_raza' => $request->fk_composicion_raza,
            'archivado' => false
        ]);

        // Create initial estado if provided
        if ($request->has('estado_inicial')) {
            EstadoAnimal::create([
                'esan_fecha_ini' => $request->estado_inicial['fecha_ini'],
                'esan_fecha_fin' => null,
                'esan_fk_estado_id' => $request->estado_inicial['estado_id'],
                'esan_fk_id_animal' => $animal->id_Animal,
            ]);
        }

        // Create initial etapa if provided
        if ($request->has('etapa_inicial')) {
            EtapaAnimal::create([
                'etan_fecha_ini' => $request->etapa_inicial['fecha_ini'],
                'etan_fecha_fin' => null,
                'etan_animal_id' => $animal->id_Animal,
                'etan_etapa_id' => $request->etapa_inicial['etapa_id'],
            ]);
        }

        $animal->load([
            'rebano.finca.propietario', 
            'composicionRaza',
            'estadoActual.estadoSalud',
            'etapaActual.etapa'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Animal creado exitosamente',
            'data' => $animal
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified animal.
     */
    public function show(Request $request, $id)
    {
        $animal = Animal::with([
            'rebano.finca.propietario', 
//            'composicionRaza',
//            'pesosCorporales',
//            'registrosCelo',
//            'reproducciones',
//            'servicios',
            'estados.estadoSalud',
//            'estadoActual.estadoSalud',
            'etapaAnimales.etapa',
            'etapaActual.etapa'
        ])->find($id);

        if (!$animal) {
            return response()->json([
                'success' => false,
                'message' => 'Animal no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }

        $user = $request->user();
        
        // Check permissions: admin can see all, propietario only their own
        if (!$user->isAdmin()) {
            $propietario = $user->propietario;
            if (!$propietario || $animal->rebano->finca->id_Propietario != $propietario->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para ver este animal'
                ], Response::HTTP_FORBIDDEN);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Detalle de animal',
            'data' => $animal
        ]);
    }

    /**
     * Update the specified animal.
     */
    public function update(Request $request, $id)
    {
        $animal = Animal::find($id);

        if (!$animal) {
            return response()->json([
                'success' => false,
                'message' => 'Animal no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }

        $validator = Validator::make($request->all(), [
            'id_Rebano' => 'sometimes|exists:rebano,id_Rebano',
            'Nombre' => 'nullable|string|max:25',
            'codigo_animal' => 'nullable|string|max:20|unique:animal,codigo_animal,' . $id . ',id_Animal',
            'Sexo' => 'sometimes|in:M,F',
            'fecha_nacimiento' => 'sometimes|date',
            'Procedencia' => 'nullable|string|max:50',
            'fk_composicion_raza' => 'sometimes|integer'
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
            if (!$propietario || $animal->rebano->finca->id_Propietario != $propietario->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para actualizar este animal'
                ], Response::HTTP_FORBIDDEN);
            }
            
            // If changing rebano, check permissions on new rebano
            if ($request->has('id_Rebano')) {
                $newRebano = Rebano::with('finca')->find($request->id_Rebano);
                if (!$newRebano || $newRebano->finca->id_Propietario != $propietario->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tiene permisos para mover el animal a ese rebaño'
                    ], Response::HTTP_FORBIDDEN);
                }
            }
        }

        $animal->update($request->only([
            'id_Rebano', 'Nombre', 'codigo_animal', 'Sexo', 
            'fecha_nacimiento', 'Procedencia', 'fk_composicion_raza'
        ]));
        
        $animal->load(['rebano.finca.propietario', 'composicionRaza']);

        return response()->json([
            'success' => true,
            'message' => 'Animal actualizado exitosamente',
            'data' => $animal
        ]);
    }

    /**
     * Remove the specified animal (soft delete).
     */
    public function destroy(Request $request, $id)
    {
        $animal = Animal::find($id);

        if (!$animal) {
            return response()->json([
                'success' => false,
                'message' => 'Animal no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }

        $user = $request->user();
        
        // Check permissions: admin can delete all, propietario only their own
        if (!$user->isAdmin()) {
            $propietario = $user->propietario;
            if (!$propietario || $animal->rebano->finca->id_Propietario != $propietario->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para eliminar este animal'
                ], Response::HTTP_FORBIDDEN);
            }
        }

        // Soft delete by setting archivado = true
        $animal->update(['archivado' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Animal eliminado exitosamente'
        ]);
    }

    /**
     * Create a new estado animal for the animal.
     */
    public function createEstadoAnimal(Request $request, $id)
    {
        $animal = Animal::find($id);

        if (!$animal) {
            return response()->json([
                'success' => false,
                'message' => 'Animal no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }

        $user = $request->user();
        
        // Check permissions
        if (!$user->isAdmin()) {
            $propietario = $user->propietario;
            if (!$propietario || $animal->rebano->finca->id_Propietario != $propietario->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para crear estado para este animal'
                ], Response::HTTP_FORBIDDEN);
            }
        }

        $validator = Validator::make($request->all(), [
            'esan_fecha_ini' => 'required|date',
            'esan_fecha_fin' => 'nullable|date|after_or_equal:esan_fecha_ini',
            'esan_fk_estado_id' => 'required|exists:estado_salud,estado_id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // If creating an active estado (no end date), close any existing active estado
        if (!$request->has('esan_fecha_fin') || $request->esan_fecha_fin === null) {
            EstadoAnimal::where('esan_fk_id_animal', $animal->id_Animal)
                ->whereNull('esan_fecha_fin')
                ->update(['esan_fecha_fin' => now()->toDateString()]);
        }

        $estadoAnimal = EstadoAnimal::create([
            'esan_fecha_ini' => $request->esan_fecha_ini,
            'esan_fecha_fin' => $request->esan_fecha_fin,
            'esan_fk_estado_id' => $request->esan_fk_estado_id,
            'esan_fk_id_animal' => $animal->id_Animal,
        ]);

        $estadoAnimal->load(['estadoSalud', 'animal']);

        return response()->json([
            'success' => true,
            'message' => 'Estado animal creado exitosamente',
            'data' => $estadoAnimal
        ], Response::HTTP_CREATED);
    }

    /**
     * Create a new etapa animal for the animal.
     */
    public function createEtapaAnimal(Request $request, $id)
    {
        $animal = Animal::find($id);

        if (!$animal) {
            return response()->json([
                'success' => false,
                'message' => 'Animal no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }

        $user = $request->user();
        
        // Check permissions
        if (!$user->isAdmin()) {
            $propietario = $user->propietario;
            if (!$propietario || $animal->rebano->finca->id_Propietario != $propietario->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para crear etapa para este animal'
                ], Response::HTTP_FORBIDDEN);
            }
        }

        $validator = Validator::make($request->all(), [
            'etan_fecha_ini' => 'required|date',
            'etan_fecha_fin' => 'nullable|date|after_or_equal:etan_fecha_ini',
            'etan_etapa_id' => 'required|exists:etapa,etapa_id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Check if etapa_animal record already exists
        $existingEtapaAnimal = EtapaAnimal::where('etan_animal_id', $animal->id_Animal)
            ->where('etan_etapa_id', $request->etan_etapa_id)
            ->first();

        if ($existingEtapaAnimal) {
            return response()->json([
                'success' => false,
                'message' => 'Ya existe un registro de etapa animal para esta etapa'
            ], Response::HTTP_CONFLICT);
        }

        // If creating an active etapa (no end date), close any existing active etapa
        if (!$request->has('etan_fecha_fin') || $request->etan_fecha_fin === null) {
            EtapaAnimal::where('etan_animal_id', $animal->id_Animal)
                ->whereNull('etan_fecha_fin')
                ->update(['etan_fecha_fin' => now()->toDateString()]);
        }

        $etapaAnimal = EtapaAnimal::create([
            'etan_fecha_ini' => $request->etan_fecha_ini,
            'etan_fecha_fin' => $request->etan_fecha_fin,
            'etan_animal_id' => $animal->id_Animal,
            'etan_etapa_id' => $request->etan_etapa_id,
        ]);

        $etapaAnimal->load(['etapa', 'animal']);

        return response()->json([
            'success' => true,
            'message' => 'Etapa animal creada exitosamente',
            'data' => $etapaAnimal
        ], Response::HTTP_CREATED);
    }

    /**
     * Update an existing estado animal.
     */
    public function updateEstadoAnimal(Request $request, $animalId, $estadoId)
    {
        $animal = Animal::find($animalId);

        if (!$animal) {
            return response()->json([
                'success' => false,
                'message' => 'Animal no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }

        $estadoAnimal = EstadoAnimal::where('esan_fk_id_animal', $animalId)
            ->where('esan_id', $estadoId)
            ->first();

        if (!$estadoAnimal) {
            return response()->json([
                'success' => false,
                'message' => 'Estado animal no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }

        $user = $request->user();
        
        // Check permissions
        if (!$user->isAdmin()) {
            $propietario = $user->propietario;
            if (!$propietario || $animal->rebano->finca->id_Propietario != $propietario->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para actualizar estado de este animal'
                ], Response::HTTP_FORBIDDEN);
            }
        }

        $validator = Validator::make($request->all(), [
            'esan_fecha_ini' => 'sometimes|date',
            'esan_fecha_fin' => 'nullable|date|after_or_equal:esan_fecha_ini',
            'esan_fk_estado_id' => 'sometimes|exists:estado_salud,estado_id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $estadoAnimal->update($request->only([
            'esan_fecha_ini',
            'esan_fecha_fin',
            'esan_fk_estado_id'
        ]));

        $estadoAnimal->load(['estadoSalud', 'animal']);

        return response()->json([
            'success' => true,
            'message' => 'Estado animal actualizado exitosamente',
            'data' => $estadoAnimal
        ], Response::HTTP_OK);
    }

    /**
     * Update an existing etapa animal.
     */
    public function updateEtapaAnimal(Request $request, $animalId, $etapaId)
    {
        $animal = Animal::find($animalId);

        if (!$animal) {
            return response()->json([
                'success' => false,
                'message' => 'Animal no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }

        $etapaAnimal = EtapaAnimal::where('etan_animal_id', $animalId)
            ->where('etan_etapa_id', $etapaId)
            ->first();

        if (!$etapaAnimal) {
            return response()->json([
                'success' => false,
                'message' => 'Etapa animal no encontrada'
            ], Response::HTTP_NOT_FOUND);
        }

        $user = $request->user();
        
        // Check permissions
        if (!$user->isAdmin()) {
            $propietario = $user->propietario;
            if (!$propietario || $animal->rebano->finca->id_Propietario != $propietario->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para actualizar etapa de este animal'
                ], Response::HTTP_FORBIDDEN);
            }
        }

        $validator = Validator::make($request->all(), [
            'etan_fecha_ini' => 'sometimes|date',
            'etan_fecha_fin' => 'nullable|date|after_or_equal:etan_fecha_ini',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $etapaAnimal->update($request->only([
            'etan_fecha_ini',
            'etan_fecha_fin'
        ]));

        $etapaAnimal->load(['etapa', 'animal']);

        return response()->json([
            'success' => true,
            'message' => 'Etapa animal actualizada exitosamente',
            'data' => $etapaAnimal
        ], Response::HTTP_OK);
    }
}
