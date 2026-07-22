<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Animal;
use App\Http\Resources\Sanidad\VacunacionResource;
use App\Http\Middleware\Legacy\Sanidad\NormalizeIndexVacunacion;
use App\Http\Middleware\Legacy\Sanidad\NormalizeShowVacunacion;
use App\Http\Middleware\Legacy\Sanidad\NormalizeStoreVacunacion;
use App\Http\Middleware\Legacy\Sanidad\NormalizeUpdateVacunacion;
use App\Services\Sanidad\VacunacionService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\Access\AuthorizationException;

class VacunacionController extends Controller
{
    public function __construct(
        protected VacunacionService $vacunacionService
    ) {
        $this->middleware(NormalizeIndexVacunacion::class)->only('index');
        $this->middleware(NormalizeShowVacunacion::class)->only('show');
        $this->middleware(NormalizeStoreVacunacion::class)->only('store');
        $this->middleware(NormalizeUpdateVacunacion::class)->only('update');
    }

    public function index(Request $request)
    {
        $filters = $request->only(['vacuna_id', 'rebano_id', 'fecha_inicio', 'fecha_fin', 'nopaginate']);
        
        $records = $this->vacunacionService->getPaginatedVacunaciones($filters, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Vacunaciones obtenidas exitosamente',
            'data'    => $this->formatCollection(VacunacionResource::class, $records),
        ], Response::HTTP_OK);
    }

    public function animalesElegibles(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'rebano_id' => 'required|exists:rebanos,id',
            'sexo'      => 'nullable|in:M,H',
            'etapa_id'  => 'nullable|integer|exists:etapas,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false, 
                'errors'  => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $animales = $this->vacunacionService->getAnimalesElegibles(
            (int) $request->input('rebano_id'),
            $request->input('sexo'),
            $request->filled('etapa_id') ? (int) $request->input('etapa_id') : null
        );

        return response()->json([
            'success' => true,
            'message' => 'Animales elegibles',
            'data'    => $animales,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'vacuna_id'         => 'required|exists:vacunas,id',
            'casa_comercial_id' => 'nullable|exists:casa_comercials,id',
            'rebano_id'         => 'required|exists:rebanos,id',
            'animal_ids'        => 'required|array|min:1',
            'animal_ids.*'      => 'integer|exists:animals,id',
            'filtros'           => 'nullable|array',
            'filtros.sexo'      => 'nullable|in:M,H',
            'filtros.etapa_id'  => 'nullable|integer|exists:etapas,id',
            'costo_dosis'       => 'required|numeric|min:0',
            'fecha'             => 'required|date',
            'observacion'       => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false, 
                'message' => 'Datos de validación incorrectos',
                'errors'  => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $vacunacion = $this->vacunacionService->createVacunacion($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Vacunación registrada exitosamente',
                'data'    => $this->formatResource(VacunacionResource::class, $vacunacion),
            ], Response::HTTP_CREATED);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    public function show($id)
    {
        try {
            $vacunacion = $this->vacunacionService->getVacunacionById((int)$id);

            return response()->json([
                'success' => true, 
                'message' => 'Vacunación obtenida exitosamente',
                'data'    => $this->formatResource(VacunacionResource::class, $vacunacion)
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Vacunación no encontrada'
            ], Response::HTTP_NOT_FOUND);
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'vacuna_id'         => 'nullable|exists:vacunas,id',
            'casa_comercial_id' => 'nullable|exists:casa_comercials,id',
            'rebano_id'         => 'nullable|exists:rebanos,id',
            'animal_ids'        => 'nullable|array|min:1',
            'animal_ids.*'      => 'integer|exists:animals,id',
            'filtros'           => 'nullable|array',
            'filtros.sexo'      => 'nullable|in:M,H',
            'filtros.etapa_id'  => 'nullable|integer|exists:etapas,id',
            'costo_dosis'       => 'nullable|numeric|min:0',
            'fecha'             => 'nullable|date',
            'observacion'       => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false, 
                'message' => 'Datos de validación incorrectos',
                'errors'  => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $vacunacion = $this->vacunacionService->updateVacunacion((int)$id, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Vacunación actualizada exitosamente',
                'data'    => $this->formatResource(VacunacionResource::class, $vacunacion),
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Vacunación no encontrada'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    public function destroy($id)
    {
        try {
            $this->vacunacionService->deleteVacunacion((int)$id);

            return response()->json([
                'success' => true, 
                'message' => 'Vacunación eliminada exitosamente'
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Vacunación no encontrada'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }
}
