<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Finca;
use App\Models\Propietario;
use App\Models\Terreno;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class FincaController extends Controller
{
    /**
     * Display a listing of fincas.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // If user is admin, show all fincas
        if ($user->isAdmin()) {
            $fincas = Finca::with(['propietario.user', 'terreno'])
                ->active()
                ->paginate(15);
        } else {
            // If user is propietario, show only their fincas
            $propietario = $user->propietario;
            if (!$propietario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no es propietario'
                ], Response::HTTP_FORBIDDEN);
            }
            
            $fincas = Finca::with(['propietario.user', 'terreno'])
                ->forPropietario($propietario->id)
                ->active()
                ->paginate(15);
        }

        return response()->json([
            'success' => true,
            'message' => 'Lista de fincas',
            'data' => $fincas
        ]);
    }

    /**
     * Store a newly created finca.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'Nombre' => 'required|string|max:25',
            'Explotacion_Tipo' => 'required|string|max:20',
            'id_Propietario' => 'required|exists:propietario,id',
            'terreno' => 'nullable|array',
            'terreno.Superficie' => 'nullable|numeric|min:0',
            'terreno.Relieve' => 'nullable|string|max:9',
            'terreno.Suelo_Textura' => 'nullable|string|max:25',
            'terreno.ph_Suelo' => 'nullable|string|max:2',
            'terreno.Precipitacion' => 'nullable|numeric|min:0',
            'terreno.Velocidad_Viento' => 'nullable|numeric|min:0',
            'terreno.Temp_Anual' => 'nullable|string|max:4',
            'terreno.Temp_Min' => 'nullable|string|max:4',
            'terreno.Temp_Max' => 'nullable|string|max:4',
            'terreno.Radiacion' => 'nullable|numeric|min:0',
            'terreno.Fuente_Agua' => 'nullable|string|max:25',
            'terreno.Caudal_Disponible' => 'nullable|integer|min:0',
            'terreno.Riego_Metodo' => 'nullable|string|max:18',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = $request->user();
        
        // Check permissions: admin can create for any propietario, propietario only for themselves
        if (!$user->isAdmin()) {
            $propietario = $user->propietario;
            if (!$propietario || $propietario->id != $request->id_Propietario) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para crear finca para este propietario'
                ], Response::HTTP_FORBIDDEN);
            }
        }

        $finca = Finca::create([
            'id_Propietario' => $request->id_Propietario,
            'Nombre' => $request->Nombre,
            'Explotacion_Tipo' => $request->Explotacion_Tipo,
            'archivado' => false
        ]);

        // Create terreno if provided
        if ($request->has('terreno') && $request->terreno) {
            $terrenoData = $request->terreno;
            $terrenoData['id_Finca'] = $finca->id_Finca;
            Terreno::create($terrenoData);
        }

        $finca->load(['propietario.user', 'terreno']);

        return response()->json([
            'success' => true,
            'message' => 'Finca creada exitosamente',
            'data' => $finca
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified finca.
     */
    public function show(Request $request, $id)
    {
        $finca = Finca::with(['propietario.user', 'terreno'])->find($id);

        if (!$finca) {
            return response()->json([
                'success' => false,
                'message' => 'Finca no encontrada'
            ], Response::HTTP_NOT_FOUND);
        }

        $user = $request->user();
        
        // Check permissions: admin can see all, propietario only their own
        if (!$user->isAdmin()) {
            $propietario = $user->propietario;
            if (!$propietario || $finca->id_Propietario != $propietario->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para ver esta finca'
                ], Response::HTTP_FORBIDDEN);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Detalle de finca',
            'data' => $finca
        ]);
    }

    /**
     * Update the specified finca.
     */
    public function update(Request $request, $id)
    {
        $finca = Finca::find($id);

        if (!$finca) {
            return response()->json([
                'success' => false,
                'message' => 'Finca no encontrada'
            ], Response::HTTP_NOT_FOUND);
        }

        $validator = Validator::make($request->all(), [
            'Nombre' => 'sometimes|string|max:25',
            'Explotacion_Tipo' => 'sometimes|string|max:20',
            'id_Propietario' => 'sometimes|exists:propietario,id',
            'terreno' => 'nullable|array',
            'terreno.Superficie' => 'nullable|numeric|min:0',
            'terreno.Relieve' => 'nullable|string|max:9',
            'terreno.Suelo_Textura' => 'nullable|string|max:25',
            'terreno.ph_Suelo' => 'nullable|string|max:2',
            'terreno.Precipitacion' => 'nullable|numeric|min:0',
            'terreno.Velocidad_Viento' => 'nullable|numeric|min:0',
            'terreno.Temp_Anual' => 'nullable|string|max:4',
            'terreno.Temp_Min' => 'nullable|string|max:4',
            'terreno.Temp_Max' => 'nullable|string|max:4',
            'terreno.Radiacion' => 'nullable|numeric|min:0',
            'terreno.Fuente_Agua' => 'nullable|string|max:25',
            'terreno.Caudal_Disponible' => 'nullable|integer|min:0',
            'terreno.Riego_Metodo' => 'nullable|string|max:18',
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
            if (!$propietario || $finca->id_Propietario != $propietario->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para actualizar esta finca'
                ], Response::HTTP_FORBIDDEN);
            }
            
            // Propietario cannot change ownership
            if ($request->has('id_Propietario') && $request->id_Propietario != $propietario->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No puede cambiar el propietario de la finca'
                ], Response::HTTP_FORBIDDEN);
            }
        }

        $finca->update($request->only(['Nombre', 'Explotacion_Tipo', 'id_Propietario']));

        // Update or create terreno if provided
        if ($request->has('terreno') && $request->terreno) {
            $terreno = $finca->terreno;
            if ($terreno) {
                // Update existing terreno
                $terreno->update($request->terreno);
            } else {
                // Create new terreno
                $terrenoData = $request->terreno;
                $terrenoData['id_Finca'] = $finca->id_Finca;
                Terreno::create($terrenoData);
            }
        }

        $finca->load(['propietario.user', 'terreno']);

        return response()->json([
            'success' => true,
            'message' => 'Finca actualizada exitosamente',
            'data' => $finca
        ]);
    }

    /**
     * Remove the specified finca (soft delete).
     */
    public function destroy(Request $request, $id)
    {
        $finca = Finca::find($id);

        if (!$finca) {
            return response()->json([
                'success' => false,
                'message' => 'Finca no encontrada'
            ], Response::HTTP_NOT_FOUND);
        }

        $user = $request->user();
        
        // Check permissions: admin can delete all, propietario only their own
        if (!$user->isAdmin()) {
            $propietario = $user->propietario;
            if (!$propietario || $finca->id_Propietario != $propietario->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para eliminar esta finca'
                ], Response::HTTP_FORBIDDEN);
            }
        }

        // Soft delete by setting archivado = true
        $finca->update(['archivado' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Finca eliminada exitosamente'
        ]);
    }
}