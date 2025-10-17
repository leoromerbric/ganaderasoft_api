<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MedidasCorporales;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class MedidasCorporalesController extends Controller
{
    /**
     * Display a listing of medidas corporales.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $query = MedidasCorporales::query();
	//$query = MedidasCorporales::with(['etapaAnimal.etapa', 'etapaAnimal.animal']);

        // Apply filters
        if ($request->has('animal_id')) {
            $query->forAnimal($request->animal_id);
        }

        if ($request->has('etapa_id')) {
            $query->forEtapa($request->etapa_id);
        }

        // If user is not admin, only show medidas from their animals
        if (!$user->isAdmin()) {
            if ($user->isPropietario()) {
             /*   $query->whereHas('etapaAnimal.animal.rebano.finca', function ($q) use ($user) {
                    $q->where('id_Propietario', $user->propietario->id);
	     });*/
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para ver esta información'
                ], Response::HTTP_FORBIDDEN);
            }
        }

        $medidasCorporales = $query->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Lista de medidas corporales obtenida exitosamente',
            'data' => $medidasCorporales->items(),
            'pagination' => [
                'current_page' => $medidasCorporales->currentPage(),
                'last_page' => $medidasCorporales->lastPage(),
                'per_page' => $medidasCorporales->perPage(),
                'total' => $medidasCorporales->total(),
            ]
        ], Response::HTTP_OK);
    }

    /**
     * Store a newly created medidas corporales.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'Altura_HC' => 'nullable|numeric|min:0',
            'Altura_HG' => 'nullable|numeric|min:0',
            'Perimetro_PT' => 'nullable|numeric|min:0',
            'Perimetro_PCA' => 'nullable|numeric|min:0',
            'Longitud_LC' => 'nullable|numeric|min:0',
            'Longitud_LG' => 'nullable|numeric|min:0',
            'Anchura_AG' => 'nullable|numeric|min:0',
            'medida_etapa_anid' => 'required|exists:animal,id_Animal',
            'medida_etapa_etid' => 'required|exists:etapa,etapa_id',
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
            ->where('etan_animal_id', $request->medida_etapa_anid)
            ->where('etan_etapa_id', $request->medida_etapa_etid)
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
                $animal = \App\Models\Animal::find($request->medida_etapa_anid);
                if (!$animal || $animal->rebano->finca->id_Propietario !== $user->propietario->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tiene permisos para registrar medidas a este animal'
                    ], Response::HTTP_FORBIDDEN);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para registrar medidas corporales'
                ], Response::HTTP_FORBIDDEN);
            }
        }

        $medidasCorporales = MedidasCorporales::create($request->all());
        //$medidasCorporales->load(['etapaAnimal.etapa', 'etapaAnimal.animal']);

        return response()->json([
            'success' => true,
            'message' => 'Medidas corporales registradas exitosamente',
            'data' => $medidasCorporales
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified medidas corporales.
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $medidasCorporales = MedidasCorporales::with(['etapaAnimal.etapa', 'etapaAnimal.animal'])->find($id);

        if (!$medidasCorporales) {
            return response()->json([
                'success' => false,
                'message' => 'Medidas corporales no encontradas'
            ], Response::HTTP_NOT_FOUND);
        }

        // Check permissions
        if (!$user->isAdmin()) {
            if ($user->isPropietario()) {
                if ($medidasCorporales->etapaAnimal->animal->rebano->finca->id_Propietario !== $user->propietario->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tiene permisos para ver estas medidas corporales'
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
            'message' => 'Medidas corporales obtenidas exitosamente',
            'data' => $medidasCorporales
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified medidas corporales.
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        $medidasCorporales = MedidasCorporales::find($id);

        if (!$medidasCorporales) {
            return response()->json([
                'success' => false,
                'message' => 'Medidas corporales no encontradas'
            ], Response::HTTP_NOT_FOUND);
        }

        // Check permissions
        if (!$user->isAdmin()) {
            if ($user->isPropietario()) {
                $medidasCorporales->load(['etapaAnimal.animal.rebano.finca']);
                if ($medidasCorporales->etapaAnimal->animal->rebano->finca->id_Propietario !== $user->propietario->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tiene permisos para editar estas medidas corporales'
                    ], Response::HTTP_FORBIDDEN);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para editar medidas corporales'
                ], Response::HTTP_FORBIDDEN);
            }
        }

        $validator = Validator::make($request->all(), [
            'Altura_HC' => 'sometimes|numeric|min:0',
            'Altura_HG' => 'sometimes|numeric|min:0',
            'Perimetro_PT' => 'sometimes|numeric|min:0',
            'Perimetro_PCA' => 'sometimes|numeric|min:0',
            'Longitud_LC' => 'sometimes|numeric|min:0',
            'Longitud_LG' => 'sometimes|numeric|min:0',
            'Anchura_AG' => 'sometimes|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $medidasCorporales->update($request->all());
        $medidasCorporales->load(['etapaAnimal.etapa', 'etapaAnimal.animal']);

        return response()->json([
            'success' => true,
            'message' => 'Medidas corporales actualizadas exitosamente',
            'data' => $medidasCorporales
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified medidas corporales.
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $medidasCorporales = MedidasCorporales::find($id);

        if (!$medidasCorporales) {
            return response()->json([
                'success' => false,
                'message' => 'Medidas corporales no encontradas'
            ], Response::HTTP_NOT_FOUND);
        }

        // Check permissions
        if (!$user->isAdmin()) {
            if ($user->isPropietario()) {
                $medidasCorporales->load(['etapaAnimal.animal.rebano.finca']);
                if ($medidasCorporales->etapaAnimal->animal->rebano->finca->id_Propietario !== $user->propietario->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tiene permisos para eliminar estas medidas corporales'
                    ], Response::HTTP_FORBIDDEN);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para eliminar medidas corporales'
                ], Response::HTTP_FORBIDDEN);
            }
        }

        $medidasCorporales->delete();

        return response()->json([
            'success' => true,
            'message' => 'Medidas corporales eliminadas exitosamente'
        ], Response::HTTP_OK);
    }
}
