<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Animal\AnimalService;
use App\Services\Animal\AnimalEstadoService;
use App\Services\Animal\AnimalEtapaService;
use App\Http\Resources\Animal\AnimalResource;
use App\Http\Resources\Animal\EstadoAnimalResource;
use App\Http\Resources\Animal\EtapaAnimalResource;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class AnimalController extends Controller
{
    /**
     * Constructor del controlador.
     * Inyecta los servicios correspondientes al dominio de Animales.
     *
     * @param AnimalService $animalService
     * @param AnimalEstadoService $estadoService
     * @param AnimalEtapaService $etapaService
     */
    public function __construct(
        private AnimalService $animalService,
        private AnimalEstadoService $estadoService,
        private AnimalEtapaService $etapaService
    ) {}

    /**
     * Devuelve una lista paginada de animales aplicando filtros y permisos.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $animals = $this->animalService->listAnimals($request->only(['rebano_id', 'sexo']), $request->user());
            
            return response()->json([
                'success' => true,
                'message' => 'Lista de animales',
                'data' => $this->formatCollection(AnimalResource::class, $animals)
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Registra un nuevo animal en el sistema con sus datos iniciales.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_Rebano' => 'required|exists:rebanos,id',
            'Nombre' => 'nullable|string|max:25',
            'codigo_animal' => 'nullable|string|max:20|unique:animals,codigo_animal',
            'Sexo' => 'required|in:M,F',
            'fecha_nacimiento' => 'required|date',
            'Procedencia' => 'nullable|string|max:50',
            'fk_composicion_raza' => 'required|exists:composicion_razas,id',
            'estado_inicial' => 'nullable|array',
            'estado_inicial.estado_id' => 'required_with:estado_inicial|exists:estado_saluds,id',
            'estado_inicial.fecha_ini' => 'required_with:estado_inicial|date',
            'etapa_inicial' => 'nullable|array',
            'etapa_inicial.etapa_id' => 'required_with:etapa_inicial|exists:etapas,id',
            'etapa_inicial.fecha_ini' => 'required_with:etapa_inicial|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            [$animal, $clasificacion] = $this->animalService->storeAnimal($request->all(), $request->user());
            
            $animal->load([
                'rebano.finca.propietario', 
                'composicionRaza',
                'estadoActual.estadoSalud',
                'etapaActual.etapa'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Animal creado exitosamente',
                'data' => $this->formatResource(AnimalResource::class, $animal),
                'clasificacion_etaria' => $clasificacion,
            ], Response::HTTP_CREATED);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Muestra el detalle completo de un animal específico.
     *
     * @param Request $request
     * @param mixed $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        try {
            $animal = $this->animalService->getAnimal((int) $id, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Detalle de animal',
                'data' => $this->formatResource(AnimalResource::class, $animal)
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Animal no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Actualiza los datos de un animal y recalcula su clasificación etaria.
     *
     * @param Request $request
     * @param mixed $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'id_Rebano' => 'sometimes|exists:rebanos,id',
            'Nombre' => 'nullable|string|max:25',
            'codigo_animal' => 'nullable|string|max:20|unique:animals,codigo_animal,' . $id . ',id',
            'Sexo' => 'sometimes|in:M,F',
            'fecha_nacimiento' => 'sometimes|date',
            'Procedencia' => 'nullable|string|max:50',
            'fk_composicion_raza' => 'sometimes|exists:composicion_razas,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            [$animal, $clasificacion] = $this->animalService->updateAnimal((int) $id, $request->all(), $request->user());
            
            $animal->load(['rebano.finca.propietario', 'composicionRaza', 'etapaActual.etapa']);

            return response()->json([
                'success' => true,
                'message' => 'Animal actualizado exitosamente',
                'data' => $this->formatResource(AnimalResource::class, $animal),
                'clasificacion_etaria' => $clasificacion,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Animal no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Elimina lógicamente (archiva) un animal específico.
     *
     * @param Request $request
     * @param mixed $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        try {
            $this->animalService->archiveAnimal((int) $id, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Animal eliminado exitosamente'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Animal no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Registra un nuevo estado de salud en el historial del animal.
     *
     * @param Request $request
     * @param mixed $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function createEstadoAnimal(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'esan_fecha_ini' => 'required|date',
            'esan_fecha_fin' => 'nullable|date|after_or_equal:esan_fecha_ini',
            'esan_fk_estado_id' => 'required|exists:estado_saluds,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $estadoAnimal = $this->estadoService->createEstado((int) $id, $request->all(), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Estado animal creado exitosamente',
                'data' => $this->formatResource(EstadoAnimalResource::class, $estadoAnimal)
            ], Response::HTTP_CREATED);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Animal no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Registra un nuevo cambio de etapa manual en el historial del animal.
     *
     * @param Request $request
     * @param mixed $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function createEtapaAnimal(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'etan_fecha_ini' => 'required|date',
            'etan_fecha_fin' => 'nullable|date|after_or_equal:etan_fecha_ini',
            'etan_etapa_id' => 'required|exists:etapas,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $etapaAnimal = $this->etapaService->createEtapa((int) $id, $request->all(), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Etapa animal creada exitosamente',
                'data' => $this->formatResource(EtapaAnimalResource::class, $etapaAnimal)
            ], Response::HTTP_CREATED);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Animal no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } catch (ConflictHttpException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_CONFLICT);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Actualiza un registro existente del historial de estados de salud del animal.
     *
     * @param Request $request
     * @param mixed $animalId
     * @param mixed $estadoId
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateEstadoAnimal(Request $request, $animalId, $estadoId)
    {
        $validator = Validator::make($request->all(), [
            'esan_fecha_ini' => 'sometimes|date',
            'esan_fecha_fin' => 'nullable|date|after_or_equal:esan_fecha_ini',
            'esan_fk_estado_id' => 'sometimes|exists:estado_saluds,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $estadoAnimal = $this->estadoService->updateEstado((int) $animalId, (int) $estadoId, $request->all(), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Estado animal actualizado exitosamente',
                'data' => $this->formatResource(EstadoAnimalResource::class, $estadoAnimal)
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Animal o registro de estado no encontrado'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Actualiza un registro existente del historial de etapas del animal.
     *
     * @param Request $request
     * @param mixed $animalId
     * @param mixed $etapaId
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateEtapaAnimal(Request $request, $animalId, $etapaId)
    {
        $validator = Validator::make($request->all(), [
            'etan_fecha_ini' => 'sometimes|date',
            'etan_fecha_fin' => 'nullable|date|after_or_equal:etan_fecha_ini',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $etapaAnimal = $this->etapaService->updateEtapa((int) $animalId, (int) $etapaId, $request->all(), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Etapa animal actualizada exitosamente',
                'data' => $this->formatResource(EtapaAnimalResource::class, $etapaAnimal)
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Animal o registro de etapa no encontrada'
            ], Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }
}
