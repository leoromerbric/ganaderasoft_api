<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Leche;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class LecheController extends Controller
{
    /**
     * Display a listing of leche.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
    $query = Leche::with(['lactancia.animal']);

        // Apply filters
        if ($request->has('lactancia_id')) {
            $query->forLactancia($request->lactancia_id);
        }

        if ($request->has('fecha_inicio')) {
            $endDate = $request->has('fecha_fin') ? $request->fecha_fin : null;
            $query->byDateRange($request->fecha_inicio, $endDate);
        }

        if ($request->has('produccion_minima')) {
            $query->minProduction($request->produccion_minima);
        }

        // If user is not admin, only show leche from their animals
        if (!$user->isAdmin() && $user->isPropietario()) {
            $propietario = $user->propietario;
            if ($propietario) {
                $query->whereHas('lactancia.animal.rebano.finca', function ($q) use ($propietario) {
                    $q->where('id_Propietario', $propietario->id);
                });
            }
        }

        $lecheRecords = $query->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Lista de registros de leche obtenida exitosamente',
            'data' => $lecheRecords->items(),
            'pagination' => [
                'current_page' => $lecheRecords->currentPage(),
                'last_page' => $lecheRecords->lastPage(),
                'per_page' => $lecheRecords->perPage(),
                'total' => $lecheRecords->total(),
            ]
        ], Response::HTTP_OK);
    }

    /**
     * Store a newly created leche record.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'leche_fecha_pesaje' => 'required|date',
            'leche_pesaje_Total' => 'required|numeric|min:0',
            'leche_lactancia_id' => 'required|exists:lactancia,lactancia_id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Check permissions
        if (!$user->isAdmin()) {
            if ($user->isPropietario()) {
                $lactancia = \App\Models\Lactancia::find($request->leche_lactancia_id);
                if (!$lactancia) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Lactancia no encontrada'
                    ], Response::HTTP_NOT_FOUND);
                }

                /*$lactancia->load(['etapaAnimal.animal.rebano.finca']);
                if ($lactancia->etapaAnimal->animal->rebano->finca->id_Propietario !== $user->propietario->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tiene permisos para registrar leche a esta lactancia'
                    ], Response::HTTP_FORBIDDEN);
		}*/
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para registrar leche'
                ], Response::HTTP_FORBIDDEN);
            }
        }

        $leche = Leche::create($request->all());
        //$leche->load(['lactancia.etapaAnimal.animal']);

        return response()->json([
            'success' => true,
            'message' => 'Registro de leche creado exitosamente',
            'data' => $leche
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified leche record.
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $leche = Leche::with(['lactancia.animal', 'lactancia.etapa'])->find($id);

        if (!$leche) {
            return response()->json([
                'success' => false,
                'message' => 'Registro de leche no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }

        // Check permissions
        if (!$user->isAdmin()) {
            if ($user->isPropietario()) {
                $ownerId = optional(optional(optional(optional($leche->lactancia)->animal)->rebano)->finca)->id_Propietario;
                if (!$ownerId || $ownerId !== $user->propietario->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tiene permisos para ver este registro de leche'
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
            'message' => 'Registro de leche obtenido exitosamente',
            'data' => $leche
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified leche record.
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        $leche = Leche::find($id);

        if (!$leche) {
            return response()->json([
                'success' => false,
                'message' => 'Registro de leche no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }

        // Check permissions
        if (!$user->isAdmin()) {
            if ($user->isPropietario()) {
                $leche->load(['lactancia.animal.rebano.finca']);
                $ownerId = optional(optional(optional(optional($leche->lactancia)->animal)->rebano)->finca)->id_Propietario;
                if (!$ownerId || $ownerId !== $user->propietario->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tiene permisos para editar este registro de leche'
                    ], Response::HTTP_FORBIDDEN);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para editar registros de leche'
                ], Response::HTTP_FORBIDDEN);
            }
        }

        $validator = Validator::make($request->all(), [
            'leche_fecha_pesaje' => 'sometimes|date',
            'leche_pesaje_Total' => 'sometimes|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $leche->update($request->all());
        $leche->load(['lactancia.animal', 'lactancia.etapa']);

        return response()->json([
            'success' => true,
            'message' => 'Registro de leche actualizado exitosamente',
            'data' => $leche
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified leche record.
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $leche = Leche::find($id);

        if (!$leche) {
            return response()->json([
                'success' => false,
                'message' => 'Registro de leche no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }

        // Check permissions
        if (!$user->isAdmin()) {
            if ($user->isPropietario()) {
                $leche->load(['lactancia.animal.rebano.finca']);
                $ownerId = optional(optional(optional(optional($leche->lactancia)->animal)->rebano)->finca)->id_Propietario;
                if (!$ownerId || $ownerId !== $user->propietario->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tiene permisos para eliminar este registro de leche'
                    ], Response::HTTP_FORBIDDEN);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para eliminar registros de leche'
                ], Response::HTTP_FORBIDDEN);
            }
        }

        $leche->delete();

        return response()->json([
            'success' => true,
            'message' => 'Registro de leche eliminado exitosamente'
        ], Response::HTTP_OK);
    }
}
