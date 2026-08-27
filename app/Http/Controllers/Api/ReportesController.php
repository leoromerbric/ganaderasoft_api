<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Reportes\EstadisticasFincasResource;
use App\Services\Reportes\ReportesService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ReportesController extends Controller
{
    public function __construct(
        protected ReportesService $reportesService
    ) {
        $this->middleware(\App\Http\Middleware\Legacy\Reportes\NormalizeEstadisticasFincas::class)->only('estadisticasFincas');
    }

    /**
     * Get statistical reports for farms (fincas).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function estadisticasFincas(Request $request)
    {
        try {
            $filters = $request->only(['propietario_id', 'finca_id']);
            $estadisticas = $this->reportesService->getEstadisticasFincas($filters, $request->user());

            return response()->json(new EstadisticasFincasResource($estadisticas));
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
    public function general(Request $request)
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
     * Reporte de Historia de Lactancias con cálculo TIM (P244, P270, P305).
     */
    public function lactancias(Request $request)
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
    public function reproductivo(Request $request)
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
     * Reporte de Histórico de Pesajes de Leche.
     */
    public function pesajeLeche(Request $request)
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
     * Reporte / Resumen de Rebaños de la Finca.
     */
    public function rebanos(Request $request)
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
