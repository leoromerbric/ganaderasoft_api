<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PesoCorporal;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class PesoCorporalController extends Controller
{
    /**
     * Display a listing of peso corporal.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
	$query = PesoCorporal::query();
	//$query = PesoCorporal::with(['etapaAnimal.etapa', 'etapaAnimal.animal']);

        // Apply filters
        if ($request->has('animal_id')) {
            $query->forAnimal($request->animal_id);
        }

        if ($request->has('fecha_inicio')) {
            $endDate = $request->has('fecha_fin') ? $request->fecha_fin : null;
            $query->byDateRange($request->fecha_inicio, $endDate);
        }

        // If user is not admin, only show peso from their animals
        if (!$user->isAdmin()) {
            if ($user->isPropietario()) {
                $query->whereHas('etapaAnimal.animal.rebano.finca', function ($q) use ($user) {
                    $q->where('id_Propietario', $user->propietario->id);
                });
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para ver esta información'
                ], Response::HTTP_FORBIDDEN);
            }
        }

        $pesoCorporal = $query->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Lista de peso corporal obtenida exitosamente',
            'data' => $pesoCorporal->items(),
            'pagination' => [
                'current_page' => $pesoCorporal->currentPage(),
                'last_page' => $pesoCorporal->lastPage(),
                'per_page' => $pesoCorporal->perPage(),
                'total' => $pesoCorporal->total(),
            ]
        ], Response::HTTP_OK);
    }

    /**
     * Store a newly created peso corporal.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'Fecha_Peso' => 'required|date',
            'Peso' => 'required|numeric|min:0',
            'Comentario' => 'nullable|string|max:40',
            'peso_etapa_anid' => 'required|exists:animal,id_Animal',
            'peso_etapa_etid' => 'required|exists:etapa,etapa_id',
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
            ->where('etan_animal_id', $request->peso_etapa_anid)
            ->where('etan_etapa_id', $request->peso_etapa_etid)
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
                $animal = \App\Models\Animal::find($request->peso_etapa_anid);
                if (!$animal || $animal->rebano->finca->id_Propietario !== $user->propietario->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tiene permisos para registrar peso a este animal'
                    ], Response::HTTP_FORBIDDEN);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para registrar peso corporal'
                ], Response::HTTP_FORBIDDEN);
            }
        }

        $pesoCorporal = PesoCorporal::create($request->all());
        //$pesoCorporal->load(['etapaAnimal.etapa', 'etapaAnimal.animal']);

        return response()->json([
            'success' => true,
            'message' => 'Peso corporal registrado exitosamente',
            'data' => $pesoCorporal
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified peso corporal.
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $pesoCorporal = PesoCorporal::with(['etapaAnimal.etapa', 'etapaAnimal.animal'])->find($id);

        if (!$pesoCorporal) {
            return response()->json([
                'success' => false,
                'message' => 'Peso corporal no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }

        // Check permissions
        if (!$user->isAdmin()) {
            if ($user->isPropietario()) {
                if ($pesoCorporal->etapaAnimal->animal->rebano->finca->id_Propietario !== $user->propietario->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tiene permisos para ver este peso corporal'
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
            'message' => 'Peso corporal obtenido exitosamente',
            'data' => $pesoCorporal
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified peso corporal.
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        $pesoCorporal = PesoCorporal::find($id);

        if (!$pesoCorporal) {
            return response()->json([
                'success' => false,
                'message' => 'Peso corporal no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }

        // Check permissions
        if (!$user->isAdmin()) {
            if ($user->isPropietario()) {
                /*$pesoCorporal->load(['etapaAnimal.animal.rebano.finca']);
                if ($pesoCorporal->etapaAnimal->animal->rebano->finca->id_Propietario !== $user->propietario->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tiene permisos para editar este peso corporal'
                    ], Response::HTTP_FORBIDDEN);
		}*/
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para editar peso corporal'
                ], Response::HTTP_FORBIDDEN);
            }
        }

        $validator = Validator::make($request->all(), [
            'Fecha_Peso' => 'sometimes|date',
            'Peso' => 'sometimes|numeric|min:0',
            'Comentario' => 'nullable|string|max:40',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $pesoCorporal->update($request->all());
        //$pesoCorporal->load(['etapaAnimal.etapa', 'etapaAnimal.animal']);

        return response()->json([
            'success' => true,
            'message' => 'Peso corporal actualizado exitosamente',
            'data' => $pesoCorporal
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified peso corporal.
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $pesoCorporal = PesoCorporal::find($id);

        if (!$pesoCorporal) {
            return response()->json([
                'success' => false,
                'message' => 'Peso corporal no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }

        // Check permissions
        if (!$user->isAdmin()) {
            if ($user->isPropietario()) {
                $pesoCorporal->load(['etapaAnimal.animal.rebano.finca']);
                if ($pesoCorporal->etapaAnimal->animal->rebano->finca->id_Propietario !== $user->propietario->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tiene permisos para eliminar este peso corporal'
                    ], Response::HTTP_FORBIDDEN);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para eliminar peso corporal'
                ], Response::HTTP_FORBIDDEN);
            }
        }

        $pesoCorporal->delete();

        return response()->json([
            'success' => true,
            'message' => 'Peso corporal eliminado exitosamente'
        ], Response::HTTP_OK);
    }
}
