<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Reportes\EstadisticasFincasResource;
use App\Services\Reportes\ReportesService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Controlador de endpoints de Reportes del Sistema.
 */
class ReportesController extends Controller
{
    public function __construct(
        protected ReportesService $reportesService
    ) {
        $this->middleware(\App\Http\Middleware\Legacy\Reportes\NormalizeEstadisticasFincas::class)->only('estadisticasFincas');
    }

    /**
     * Obtiene el reporte estadístico consolidado de fincas.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function estadisticasFincas(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['propietario_id', 'finca_id']);
            $estadisticas = $this->reportesService->getEstadisticasFincas($filters, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Estadísticas de fincas',
                'data'    => $this->formatResource(EstadisticasFincasResource::class, $estadisticas),
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_FORBIDDEN);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Reporte General de Finca (Animales, pesos, genealogía).
     */
    public function general(Request $request): JsonResponse
    {
        try {
            $data = $this->reportesService->getReporteGeneral($request->all(), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Reporte general obtenido exitosamente.',
                'data'    => $data,
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_FORBIDDEN);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Alias para compatibilidad: reporteGeneral
     */
    public function reporteGeneral(Request $request): JsonResponse
    {
        return $this->general($request);
    }

    /**
     * Reporte de Historia de Lactancias con cálculo TIM (P244, P270, P305).
     */
    public function lactancias(Request $request): JsonResponse
    {
        try {
            $data = $this->reportesService->getReporteLactancias($request->all(), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Reporte de lactancias obtenido exitosamente.',
                'data'    => $data,
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_FORBIDDEN);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Reporte de Historial Reproductivo Consolidado (Partos + Servicios).
     */
    public function reproductivo(Request $request): JsonResponse
    {
        try {
            $data = $this->reportesService->getReporteReproductivo($request->all(), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Reporte reproductivo obtenido exitosamente.',
                'data'    => $data,
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_FORBIDDEN);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Alias para compatibilidad: reporteReproductivo
     */
    public function reporteReproductivo(Request $request): JsonResponse
    {
        return $this->reproductivo($request);
    }

    /**
     * Reporte de Histórico de Pesajes de Leche.
     */
    public function pesajeLeche(Request $request): JsonResponse
    {
        try {
            $data = $this->reportesService->getReportePesajeLeche($request->all(), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Reporte de pesaje de leche obtenido exitosamente.',
                'data'    => $data,
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_FORBIDDEN);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Alias para compatibilidad: reportePesajeLeche
     */
    public function reportePesajeLeche(Request $request): JsonResponse
    {
        return $this->pesajeLeche($request);
    }

    /**
     * Reporte / Resumen de Rebaños de la Finca.
     */
    public function rebanos(Request $request): JsonResponse
    {
        try {
            $data = $this->reportesService->getReporteRebanos($request->all(), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Reporte de rebaños obtenido exitosamente.',
                'data'    => $data,
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_FORBIDDEN);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_NOT_FOUND);
        }
    }
}
