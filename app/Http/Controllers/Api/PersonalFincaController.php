<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PersonalFinca;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class PersonalFincaController extends Controller
{
    /**
     * Display a listing of personal finca.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $query = PersonalFinca::with(['finca']);

        // Apply filters
        if ($request->has('id_finca')) {
            $query->forFinca($request->id_finca);
        }

        if ($request->has('tipo_trabajador')) {
            $query->byTipoTrabajador($request->tipo_trabajador);
        }

        if ($request->has('nombre')) {
            $query->byName($request->nombre);
        }

        // If user is not admin, only show personal from their finca
        if (!$user->isAdmin()) {
            if ($user->isPropietario()) {
                $fincaIds = $user->propietario->fincas->pluck('id_Finca');
                $query->whereIn('id_Finca', $fincaIds);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para ver esta informacion'
                ], Response::HTTP_FORBIDDEN);
            }
        }

        $personalFinca = $query->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Lista de personal de finca obtenida exitosamente',
            'data' => $personalFinca->items(),
            'pagination' => [
                'current_page' => $personalFinca->currentPage(),
                'last_page' => $personalFinca->lastPage(),
                'per_page' => $personalFinca->perPage(),
                'total' => $personalFinca->total(),
            ]
        ], Response::HTTP_OK);
    }

    /**
     * Store a newly created personal finca.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'id_Finca' => 'required|exists:finca,id_Finca',
            'Cedula' => 'required|integer|unique:personal_finca,Cedula',
            'Nombre' => 'required|string|max:25',
            'Apellido' => 'required|string|max:25',
            'Telefono' => 'required|string|max:15',
            'Correo' => 'required|email|max:40',
            'Tipo_Trabajador' => 'required|string|max:20',
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
                $fincaIds = $user->propietario->fincas->pluck('id_Finca');
                if (!$fincaIds->contains($request->id_Finca)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tiene permisos para agregar personal a esta finca'
                    ], Response::HTTP_FORBIDDEN);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para agregar personal'
                ], Response::HTTP_FORBIDDEN);
            }
        }

        $personalFinca = PersonalFinca::create($request->all());
        $personalFinca->load(['finca']);

        return response()->json([
            'success' => true,
            'message' => 'Personal de finca creado exitosamente',
            'data' => $personalFinca
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified personal finca.
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $personalFinca = PersonalFinca::with(['finca'])->find($id);

        if (!$personalFinca) {
            return response()->json([
                'success' => false,
                'message' => 'Personal de finca no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }

        // Check permissions
        if (!$user->isAdmin()) {
            if ($user->isPropietario()) {
                $fincaIds = $user->propietario->fincas->pluck('id_Finca');
                if (!$fincaIds->contains($personalFinca->id_Finca)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tiene permisos para ver este personal'
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
            'message' => 'Personal de finca obtenido exitosamente',
            'data' => $personalFinca
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified personal finca.
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        $personalFinca = PersonalFinca::find($id);

        if (!$personalFinca) {
            return response()->json([
                'success' => false,
                'message' => 'Personal de finca no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }

        // Check permissions
        if (!$user->isAdmin()) {
            if ($user->isPropietario()) {
                $fincaIds = $user->propietario->fincas->pluck('id_Finca');
                if (!$fincaIds->contains($personalFinca->id_Finca)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tiene permisos para editar este personal'
                    ], Response::HTTP_FORBIDDEN);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para editar personal'
                ], Response::HTTP_FORBIDDEN);
            }
        }

        $validator = Validator::make($request->all(), [
            'id_Finca' => 'sometimes|exists:finca,id_Finca',
            'Cedula' => 'sometimes|integer|unique:personal_finca,Cedula,' . $id . ',id_Tecnico',
            'Nombre' => 'sometimes|string|max:25',
            'Apellido' => 'sometimes|string|max:25',
            'Telefono' => 'sometimes|string|max:15',
            'Correo' => 'sometimes|email|max:40',
            'Tipo_Trabajador' => 'sometimes|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $personalFinca->update($request->all());
        $personalFinca->load(['finca']);

        return response()->json([
            'success' => true,
            'message' => 'Personal de finca actualizado exitosamente',
            'data' => $personalFinca
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified personal finca.
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $personalFinca = PersonalFinca::find($id);

        if (!$personalFinca) {
            return response()->json([
                'success' => false,
                'message' => 'Personal de finca no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }

        // Check permissions
        if (!$user->isAdmin()) {
            if ($user->isPropietario()) {
                $fincaIds = $user->propietario->fincas->pluck('id_Finca');
                if (!$fincaIds->contains($personalFinca->id_Finca)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tiene permisos para eliminar este personal'
                    ], Response::HTTP_FORBIDDEN);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para eliminar personal'
                ], Response::HTTP_FORBIDDEN);
            }
        }

        $personalFinca->delete();

        return response()->json([
            'success' => true,
            'message' => 'Personal de finca eliminado exitosamente'
        ], Response::HTTP_OK);
    }
}
