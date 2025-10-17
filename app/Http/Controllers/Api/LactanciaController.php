<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lactancia;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class LactanciaController extends Controller
{
    /**
     * Display a listing of lactancia.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
	$query = Lactancia::query();

	//$query = Lactancia::with(['etapaAnimal.etapa', 'etapaAnimal.animal', 'lecheRecords']);

        // Apply filters
        if ($request->has('animal_id')) {
            $query->forAnimal($request->animal_id);
        }

        if ($request->has('activa')) {
            if ($request->activa == '1' || $request->activa === true) {
                $query->active();
            }
        }

        if ($request->has('fecha_inicio')) {
            $endDate = $request->has('fecha_fin') ? $request->fecha_fin : null;
            $query->byDateRange($request->fecha_inicio, $endDate);
        }

        // If user is not admin, only show lactancias from their animals
        if (!$user->isAdmin()) {
            if ($user->isPropietario()) {
               /* $query->whereHas('etapaAnimal.animal.rebano.finca', function ($q) use ($user) {
                    $q->where('id_Propietario', $user->propietario->id);
	       });*/
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para ver esta información'
                ], Response::HTTP_FORBIDDEN);
            }
        }

        $lactancias = $query->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Lista de lactancias obtenida exitosamente',
            'data' => $lactancias->items(),
            'pagination' => [
                'current_page' => $lactancias->currentPage(),
                'last_page' => $lactancias->lastPage(),
                'per_page' => $lactancias->perPage(),
                'total' => $lactancias->total(),
            ]
        ], Response::HTTP_OK);
    }

    /**
     * Store a newly created lactancia.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'lactancia_fecha_inicio' => 'required|date',
            'Lactancia_fecha_fin' => 'nullable|date|after:lactancia_fecha_inicio',
            'lactancia_secado' => 'nullable|date',
            'lactancia_etapa_anid' => 'required|exists:animal,id_Animal',
            'lactancia_etapa_etid' => 'required|exists:etapa,etapa_id',
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
            ->where('etan_animal_id', $request->lactancia_etapa_anid)
            ->where('etan_etapa_id', $request->lactancia_etapa_etid)
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
                /*$animal = \App\Models\Animal::find($request->lactancia_etapa_anid);
                if (!$animal || $animal->rebano->finca->id_Propietario !== $user->propietario->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tiene permisos para registrar lactancia a este animal'
		    ], Response::HTTP_FORBIDDEN);
	    }*/
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para registrar lactancias'
                ], Response::HTTP_FORBIDDEN);
            }
        }

        $lactancia = Lactancia::create($request->all());
       // $lactancia->load(['etapaAnimal.etapa', 'etapaAnimal.animal']);

        return response()->json([
            'success' => true,
            'message' => 'Lactancia registrada exitosamente',
            'data' => $lactancia
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified lactancia.
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $lactancia = Lactancia::with(['etapaAnimal.etapa', 'etapaAnimal.animal', 'lecheRecords'])->find($id);

        if (!$lactancia) {
            return response()->json([
                'success' => false,
                'message' => 'Lactancia no encontrada'
            ], Response::HTTP_NOT_FOUND);
        }

        // Check permissions
        if (!$user->isAdmin()) {
            if ($user->isPropietario()) {
                if ($lactancia->etapaAnimal->animal->rebano->finca->id_Propietario !== $user->propietario->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tiene permisos para ver esta lactancia'
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
            'message' => 'Lactancia obtenida exitosamente',
            'data' => $lactancia
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified lactancia.
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        $lactancia = Lactancia::find($id);

        if (!$lactancia) {
            return response()->json([
                'success' => false,
                'message' => 'Lactancia no encontrada'
            ], Response::HTTP_NOT_FOUND);
        }

        // Check permissions
        if (!$user->isAdmin()) {
            if ($user->isPropietario()) {
                $lactancia->load(['etapaAnimal.animal.rebano.finca']);
                if ($lactancia->etapaAnimal->animal->rebano->finca->id_Propietario !== $user->propietario->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tiene permisos para editar esta lactancia'
                    ], Response::HTTP_FORBIDDEN);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para editar lactancias'
                ], Response::HTTP_FORBIDDEN);
            }
        }

        $validator = Validator::make($request->all(), [
            'lactancia_fecha_inicio' => 'sometimes|date',
            'Lactancia_fecha_fin' => 'nullable|date|after:lactancia_fecha_inicio',
            'lactancia_secado' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $lactancia->update($request->all());
        $lactancia->load(['etapaAnimal.etapa', 'etapaAnimal.animal']);

        return response()->json([
            'success' => true,
            'message' => 'Lactancia actualizada exitosamente',
            'data' => $lactancia
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified lactancia.
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $lactancia = Lactancia::find($id);

        if (!$lactancia) {
            return response()->json([
                'success' => false,
                'message' => 'Lactancia no encontrada'
            ], Response::HTTP_NOT_FOUND);
        }

        // Check permissions
        if (!$user->isAdmin()) {
            if ($user->isPropietario()) {
                $lactancia->load(['etapaAnimal.animal.rebano.finca']);
                if ($lactancia->etapaAnimal->animal->rebano->finca->id_Propietario !== $user->propietario->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tiene permisos para eliminar esta lactancia'
                    ], Response::HTTP_FORBIDDEN);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para eliminar lactancias'
                ], Response::HTTP_FORBIDDEN);
            }
        }

        $lactancia->delete();

        return response()->json([
            'success' => true,
            'message' => 'Lactancia eliminada exitosamente'
        ], Response::HTTP_OK);
    }
}
