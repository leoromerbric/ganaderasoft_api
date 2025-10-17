<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CambiosAnimal;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class CambiosAnimalController extends Controller
{
    /**
     * Display a listing of cambios animal.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        //$query = CambiosAnimal::with(['etapaAnimal.etapa', 'etapaAnimal.animal']);

	$query = CambiosAnimal::query();
        // Apply filters
        if ($request->has('animal_id')) {
            $query->forAnimal($request->animal_id);
        }

        if ($request->has('etapa_id')) {
            $query->forEtapa($request->etapa_id);
        }

        if ($request->has('etapa_cambio')) {
            $query->byEtapaCambio($request->etapa_cambio);
        }

        if ($request->has('fecha_inicio')) {
            $endDate = $request->has('fecha_fin') ? $request->fecha_fin : null;
            $query->byDateRange($request->fecha_inicio, $endDate);
        }

        // If user is not admin, only show cambios from their animals
        if (!$user->isAdmin()) {
            if ($user->isPropietario()) {
                /*$query->whereHas('etapaAnimal.animal.rebano.finca', function ($q) use ($user) {
                    $q->where('id_Propietario', $user->propietario->id);
		});*/
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para ver esta información'
                ], Response::HTTP_FORBIDDEN);
            }
        }

        $cambiosAnimal = $query->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Lista de cambios de animal obtenida exitosamente',
            'data' => $cambiosAnimal->items(),
            'pagination' => [
                'current_page' => $cambiosAnimal->currentPage(),
                'last_page' => $cambiosAnimal->lastPage(),
                'per_page' => $cambiosAnimal->perPage(),
                'total' => $cambiosAnimal->total(),
            ]
        ], Response::HTTP_OK);
    }

    /**
     * Store a newly created cambios animal.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'Fecha_Cambio' => 'nullable|date',
            'Etapa_Cambio' => 'nullable|string|max:10',
            'Peso' => 'required|numeric|min:0',
            'Altura' => 'required|numeric|min:0',
            'Comentario' => 'nullable|string|max:60',
            'cambios_etapa_anid' => 'required|exists:animal,id_Animal',
            'cambios_etapa_etid' => 'required|exists:etapa,etapa_id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Check if etapa_animal exists
        $etapaAnimalExists = \DB::table('etapa_animal')
            ->where('etan_animal_id', $request->cambios_etapa_anid)
            ->where('etan_etapa_id', $request->cambios_etapa_etid)
            ->exists();

        if (!$etapaAnimalExists) {
            return response()->json([
                'success' => false,
                'message' => 'La relación etapa-animal no existe'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Check permissions
        if (!$user->isAdmin()) {
            if ($user->isPropietario()) {
                $animal = \App\Models\Animal::find($request->cambios_etapa_anid);
                if (!$animal || $animal->rebano->finca->id_Propietario !== $user->propietario->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tiene permisos para registrar cambios a este animal'
                    ], Response::HTTP_FORBIDDEN);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para registrar cambios de animal'
                ], Response::HTTP_FORBIDDEN);
            }
        }

        $cambiosAnimal = CambiosAnimal::create($request->all());
        //$cambiosAnimal->load(['etapaAnimal.etapa', 'etapaAnimal.animal']);

        return response()->json([
            'success' => true,
            'message' => 'Cambios de animal registrados exitosamente',
            'data' => $cambiosAnimal
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified cambios animal.
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $cambiosAnimal = CambiosAnimal::with(['etapaAnimal.etapa', 'etapaAnimal.animal'])->find($id);

        if (!$cambiosAnimal) {
            return response()->json([
                'success' => false,
                'message' => 'Cambios de animal no encontrados'
            ], Response::HTTP_NOT_FOUND);
        }

        // Check permissions
        if (!$user->isAdmin()) {
            if ($user->isPropietario()) {
                if ($cambiosAnimal->etapaAnimal->animal->rebano->finca->id_Propietario !== $user->propietario->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tiene permisos para ver estos cambios de animal'
                    ], Response::HTTP_FORBIDDEN);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para ver esta información'
                ], Response::HTTP_FORBIDDEN);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Cambios de animal obtenidos exitosamente',
            'data' => $cambiosAnimal
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified cambios animal.
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        $cambiosAnimal = CambiosAnimal::find($id);

        if (!$cambiosAnimal) {
            return response()->json([
                'success' => false,
                'message' => 'Cambios de animal no encontrados'
            ], Response::HTTP_NOT_FOUND);
        }

        // Check permissions
        if (!$user->isAdmin()) {
            if ($user->isPropietario()) {
                $cambiosAnimal->load(['etapaAnimal.animal.rebano.finca']);
                if ($cambiosAnimal->etapaAnimal->animal->rebano->finca->id_Propietario !== $user->propietario->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tiene permisos para editar estos cambios de animal'
                    ], Response::HTTP_FORBIDDEN);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para editar cambios de animal'
                ], Response::HTTP_FORBIDDEN);
            }
        }

        $validator = Validator::make($request->all(), [
            'Fecha_Cambio' => 'sometimes|date',
            'Etapa_Cambio' => 'sometimes|string|max:10',
            'Peso' => 'sometimes|numeric|min:0',
            'Altura' => 'sometimes|numeric|min:0',
            'Comentario' => 'nullable|string|max:60',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $cambiosAnimal->update($request->all());
        $cambiosAnimal->load(['etapaAnimal.etapa', 'etapaAnimal.animal']);

        return response()->json([
            'success' => true,
            'message' => 'Cambios de animal actualizados exitosamente',
            'data' => $cambiosAnimal
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified cambios animal.
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $cambiosAnimal = CambiosAnimal::find($id);

        if (!$cambiosAnimal) {
            return response()->json([
                'success' => false,
                'message' => 'Cambios de animal no encontrados'
            ], Response::HTTP_NOT_FOUND);
        }

        // Check permissions
        if (!$user->isAdmin()) {
            if ($user->isPropietario()) {
                $cambiosAnimal->load(['etapaAnimal.animal.rebano.finca']);
                if ($cambiosAnimal->etapaAnimal->animal->rebano->finca->id_Propietario !== $user->propietario->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tiene permisos para eliminar estos cambios de animal'
                    ], Response::HTTP_FORBIDDEN);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para eliminar cambios de animal'
                ], Response::HTTP_FORBIDDEN);
            }
        }

        $cambiosAnimal->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cambios de animal eliminados exitosamente'
        ], Response::HTTP_OK);
    }
}
