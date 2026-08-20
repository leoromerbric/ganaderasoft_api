<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Sanidad\VacunacionResource;
use App\Services\Sanidad\VacunacionService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

class VacunacionController extends Controller
{
    public function __construct(
        protected VacunacionService $vacunacionService
    ) {
    }

    /**
     * Listar registros de vacunación con filtros y paginación.
     */
    public function index(Request $request)
    {
        try {
            $filters = $request->only([
                'animal_id', 'vacuna_id', 'rebano_id', 'finca_id', 
                'fecha_inicio', 'fecha_fin', 'nopaginate'
            ]);
            $perPage = (int) $request->input('per_page', 15);

            $records = $this->vacunacionService->getPaginatedVacunaciones($filters, $request->user(), $perPage);

            return response()->json([
                'success' => true,
                'message' => 'Registros de vacunación obtenidos exitosamente',
                'data'    => $this->formatCollection(VacunacionResource::class, $records),
            ], Response::HTTP_OK);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Obtener lista de animales activos elegibles para vacunación según rebaño, sexo y etapa.
     */
    public function animalesElegibles(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'rebano_id' => 'required|integer|exists:rebanos,id',
            'sexo'      => 'nullable|in:M,H',
            'etapa_id'  => 'nullable|integer|exists:etapas,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Parámetros de búsqueda inválidos',
                'errors'  => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $animales = $this->vacunacionService->getAnimalesElegibles($request->only([
                'rebano_id', 'sexo', 'etapa_id'
            ]), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Animales elegibles obtenidos exitosamente',
                'data'    => $animales,
            ], Response::HTTP_OK);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Registrar una o múltiples vacunaciones (soporta individual o masivo).
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'vacuna_id'     => 'required|integer|exists:vacunas,id',
            'persona_id'    => 'nullable|integer|exists:personas,id',
            'fecha'         => 'required|date',
            'dosis'         => 'nullable|numeric|min:0',
            'costo'         => 'nullable|numeric|min:0',
            'lote'          => 'nullable|string|max:50',
            'observacion'   => 'nullable|string',
            'animal_id'     => 'required_without:animal_ids|integer|exists:animals,id',
            'animal_ids'    => 'required_without:animal_id|array|min:1',
            'animal_ids.*'  => 'integer|exists:animals,id',
        ], [
            'vacuna_id.required'  => 'Debe seleccionar una vacuna válida.',
            'fecha.required'      => 'La fecha de vacunación es obligatoria.',
            'animal_id.required_without' => 'Debe indicar al menos un animal a vacunar.',
            'animal_ids.required_without' => 'Debe indicar al menos un animal a vacunar.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de vacunación inválidos',
                'errors'  => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $createdRecords = $this->vacunacionService->createVacunacion($request->only([
                'vacuna_id', 'persona_id', 'fecha', 'dosis', 'costo', 'lote', 'observacion',
                'animal_id', 'animal_ids'
            ]), $request->user());

            if (count($createdRecords) === 1) {
                return response()->json([
                    'success' => true,
                    'message' => 'Vacunación registrada exitosamente',
                    'data'    => $this->formatResource(VacunacionResource::class, $createdRecords[0]),
                ], Response::HTTP_CREATED);
            }

            return response()->json([
                'success' => true,
                'message' => sprintf('Se registraron exitosamente %d vacunaciones', count($createdRecords)),
                'total_registrados' => count($createdRecords),
                'data'    => $this->formatCollection(VacunacionResource::class, collect($createdRecords)),
            ], Response::HTTP_CREATED);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors'  => $e->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * Ver detalle de un registro de vacunación.
     */
    public function show($id)
    {
        try {
            $vacunacion = $this->vacunacionService->getVacunacionById((int)$id, request()->user());

            return response()->json([
                'success' => true,
                'message' => 'Registro de vacunación obtenido exitosamente',
                'data'    => $this->formatResource(VacunacionResource::class, $vacunacion),
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registro de vacunación no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Actualizar los datos de un registro de vacunación individual.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'animal_id'   => 'sometimes|required|integer|exists:animals,id',
            'vacuna_id'   => 'sometimes|required|integer|exists:vacunas,id',
            'persona_id'  => 'nullable|integer|exists:personas,id',
            'fecha'       => 'sometimes|required|date',
            'dosis'       => 'nullable|numeric|min:0',
            'costo'       => 'nullable|numeric|min:0',
            'lote'        => 'nullable|string|max:50',
            'observacion' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors'  => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $vacunacion = $this->vacunacionService->updateVacunacion((int)$id, $validator->validated(), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Vacunación actualizada exitosamente',
                'data'    => $this->formatResource(VacunacionResource::class, $vacunacion),
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registro de vacunación no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Eliminar un registro de vacunación.
     */
    public function destroy($id)
    {
        try {
            $this->vacunacionService->deleteVacunacion((int)$id, request()->user());

            return response()->json([
                'success' => true,
                'message' => 'Registro de vacunación eliminado exitosamente'
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registro de vacunación no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }
}
