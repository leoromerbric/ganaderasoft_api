<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use App\Services\PersonalFinca\PersonalFincaService;
use App\Http\Resources\PersonalFinca\PersonalFincaResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PersonalFincaController extends Controller
{
    protected $personalFincaService;

    public function __construct(PersonalFincaService $personalFincaService)
    {
        $this->personalFincaService = $personalFincaService;
        
        $this->middleware(\App\Http\Middleware\Legacy\PersonalFinca\NormalizeIndex::class)->only('index');
        $this->middleware(\App\Http\Middleware\Legacy\PersonalFinca\NormalizeStore::class)->only('store');
        $this->middleware(\App\Http\Middleware\Legacy\PersonalFinca\NormalizeShow::class)->only('show');
        $this->middleware(\App\Http\Middleware\Legacy\PersonalFinca\NormalizeUpdate::class)->only('update');
    }

    public function index(Request $request)
    {
        try {
            $filters = $request->only(['finca_id', 'tipo_trabajador_id', 'nombre']);
            $personal = $this->personalFincaService->listPersonal($filters, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Lista de personal de finca obtenida exitosamente',
                'data' => PersonalFincaResource::collection($personal)->response()->getData(true)['data'],
                'meta' => [
                    'current_page' => $personal->currentPage(),
                    'last_page' => $personal->lastPage(),
                    'per_page' => $personal->perPage(),
                    'total' => $personal->total(),
                ]
            ], Response::HTTP_OK);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'finca_id' => 'required|exists:fincas,id',
            'cedula' => 'required|string|regex:/^[VEJPG][0-9]+$/',
            'nombre' => 'required|string|max:25',
            'apellido' => 'required|string|max:25',
            'telefono' => 'required|string|max:15',
            'correo' => 'required|email|max:40',
            'tipo_trabajador_id' => 'required|exists:tipo_trabajadors,id',
            'status' => 'boolean',
            'fecha_ingreso' => 'date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $personal = $this->personalFincaService->storePersonal($request->all(), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Personal de finca creado exitosamente',
                'data' => new PersonalFincaResource($personal)
            ], Response::HTTP_CREATED);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    public function show(Request $request, $id)
    {
        try {
            $personal = $this->personalFincaService->getPersonal($id, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Personal de finca obtenido exitosamente',
                'data' => new PersonalFincaResource($personal)
            ], Response::HTTP_OK);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Personal de finca no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'finca_id' => 'sometimes|exists:fincas,id',
            'cedula' => 'sometimes|string|regex:/^[VEJPG][0-9]+$/',
            'nombre' => 'sometimes|string|max:25',
            'apellido' => 'sometimes|string|max:25',
            'telefono' => 'sometimes|string|max:15',
            'correo' => 'sometimes|email|max:40',
            'tipo_trabajador_id' => 'sometimes|exists:tipo_trabajadors,id',
            'status' => 'sometimes|boolean',
            'fecha_ingreso' => 'sometimes|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $personal = $this->personalFincaService->updatePersonal($id, $request->all(), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Personal de finca actualizado exitosamente',
                'data' => new PersonalFincaResource($personal)
            ], Response::HTTP_OK);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Personal de finca no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $this->personalFincaService->deletePersonal($id, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Personal de finca eliminado exitosamente'
            ], Response::HTTP_OK);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Personal de finca no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }
    }
}
