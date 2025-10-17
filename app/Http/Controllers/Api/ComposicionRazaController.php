<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ComposicionRaza;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class ComposicionRazaController extends Controller
{
    /**
     * Display a listing of composicion raza.
     */
    public function index(Request $request)
    {
        $query = ComposicionRaza::with(['finca', 'tipoAnimal']);

        // Apply filters (removed fk_tipo_animal_id and fk_id_Finca filtering as requested)
        if ($request->has('nombre')) {
            $query->byName($request->nombre);
        }

        $composicionRazas = $query->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Lista de composiciones de raza obtenida exitosamente',
            'data' => $composicionRazas->items(),
            'pagination' => [
                'current_page' => $composicionRazas->currentPage(),
                'last_page' => $composicionRazas->lastPage(),
                'per_page' => $composicionRazas->perPage(),
                'total' => $composicionRazas->total(),
            ]
        ], Response::HTTP_OK);
    }

    /**
     * Store a newly created composicion raza.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'Nombre' => 'required|string|max:30',
            'Siglas' => 'nullable|string|max:6',
            'Pelaje' => 'nullable|string|max:80',
            'Proposito' => 'nullable|string|max:15',
            'Tipo_Raza' => 'nullable|string|max:12',
            'Origen' => 'nullable|string|max:60',
            'Caracteristica_Especial' => 'nullable|string|max:80',
            'Proporcion_Raza' => 'nullable|string|max:7',
            'fk_id_Finca' => 'nullable|exists:finca,id_Finca',
            'fk_tipo_animal_id' => 'nullable|exists:tipo_animal,tipo_animal_id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = $request->user();

        // Check permissions if finca is specified
        if ($request->has('fk_id_Finca') && !$user->isAdmin()) {
            $propietario = $user->propietario;
            if (!$propietario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no tiene propietario asociado'
                ], Response::HTTP_FORBIDDEN);
            }

            $finca = \App\Models\Finca::find($request->fk_id_Finca);
            if (!$finca || $finca->id_Propietario != $propietario->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para asociar esta composición con la finca especificada'
                ], Response::HTTP_FORBIDDEN);
            }
        }

        $composicionRaza = ComposicionRaza::create($request->all());
        $composicionRaza->load(['finca', 'tipoAnimal']);

        return response()->json([
            'success' => true,
            'message' => 'Composición de raza creada exitosamente',
            'data' => $composicionRaza
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified composicion raza.
     */
    public function show(Request $request, $id)
    {
        $composicionRaza = ComposicionRaza::with(['finca', 'tipoAnimal', 'animales'])->find($id);

        if (!$composicionRaza) {
            return response()->json([
                'success' => false,
                'message' => 'Composición de raza no encontrada'
            ], Response::HTTP_NOT_FOUND);
        }

        $user = $request->user();

        // Check permissions
        if (!$user->isAdmin() && $composicionRaza->finca) {
            $propietario = $user->propietario;
            if (!$propietario || $composicionRaza->finca->id_Propietario != $propietario->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para ver esta composición de raza'
                ], Response::HTTP_FORBIDDEN);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Composición de raza obtenida exitosamente',
            'data' => $composicionRaza
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified composicion raza.
     */
    public function update(Request $request, $id)
    {
        $composicionRaza = ComposicionRaza::find($id);

        if (!$composicionRaza) {
            return response()->json([
                'success' => false,
                'message' => 'Composición de raza no encontrada'
            ], Response::HTTP_NOT_FOUND);
        }

        $validator = Validator::make($request->all(), [
            'Nombre' => 'sometimes|string|max:30',
            'Siglas' => 'nullable|string|max:6',
            'Pelaje' => 'nullable|string|max:80',
            'Proposito' => 'nullable|string|max:15',
            'Tipo_Raza' => 'nullable|string|max:12',
            'Origen' => 'nullable|string|max:60',
            'Caracteristica_Especial' => 'nullable|string|max:80',
            'Proporcion_Raza' => 'nullable|string|max:7',
            'fk_id_Finca' => 'nullable|exists:finca,id_Finca',
            'fk_tipo_animal_id' => 'nullable|exists:tipo_animal,tipo_animal_id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = $request->user();

        // Check permissions
        if (!$user->isAdmin() && $composicionRaza->finca) {
            $propietario = $user->propietario;
            if (!$propietario || $composicionRaza->finca->id_Propietario != $propietario->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para actualizar esta composición de raza'
                ], Response::HTTP_FORBIDDEN);
            }
        }

        $composicionRaza->update($request->all());
        $composicionRaza->load(['finca', 'tipoAnimal']);

        return response()->json([
            'success' => true,
            'message' => 'Composición de raza actualizada exitosamente',
            'data' => $composicionRaza
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified composicion raza.
     */
    public function destroy(Request $request, $id)
    {
        $composicionRaza = ComposicionRaza::find($id);

        if (!$composicionRaza) {
            return response()->json([
                'success' => false,
                'message' => 'Composición de raza no encontrada'
            ], Response::HTTP_NOT_FOUND);
        }

        $user = $request->user();

        // Check permissions
        if (!$user->isAdmin() && $composicionRaza->finca) {
            $propietario = $user->propietario;
            if (!$propietario || $composicionRaza->finca->id_Propietario != $propietario->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para eliminar esta composición de raza'
                ], Response::HTTP_FORBIDDEN);
            }
        }

        // Check if there are animals using this composition
        if ($composicionRaza->animales()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar la composición de raza porque tiene animales asociados'
            ], Response::HTTP_CONFLICT);
        }

        $composicionRaza->delete();

        return response()->json([
            'success' => true,
            'message' => 'Composición de raza eliminada exitosamente'
        ], Response::HTTP_OK);
    }
}