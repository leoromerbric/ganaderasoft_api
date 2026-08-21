<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Reportes\EstadisticasFincasResource;
use App\Http\Resources\Reportes\ReporteGeneralResource;
use App\Http\Resources\Reportes\ReportePesajeLecheResource;
use App\Http\Resources\Reportes\ReporteReproductivoResource;
use App\Services\Reportes\ReporteFincasService;
use App\Services\Reportes\ReporteGeneralService;
use App\Services\Reportes\ReportePesajeLecheService;
use App\Services\Reportes\ReporteReproductivoService;
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
    public function __construct()
    {
        $this->middleware(\App\Http\Middleware\Legacy\Reportes\NormalizeEstadisticasFincas::class)->only('estadisticasFincas');
    }

    /**
     * Obtiene el reporte estadístico consolidado de fincas.
     *
     * @param Request $request
     * @param ReporteFincasService $service
     * @return JsonResponse
     */
    public function estadisticasFincas(Request $request, ReporteFincasService $service): JsonResponse
    {
        try {
            $filters = $request->only(['propietario_id', 'finca_id']);
            $estadisticas = $service->getEstadisticasFincas($filters, $request->user());

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
     * Endpoint para generar el Reporte General de Finca / Inventario Ganadero.
     *
     * @param Request $request
     * @param ReporteGeneralService $service
     * @return JsonResponse
     */
    public function reporteGeneral(Request $request, ReporteGeneralService $service): JsonResponse
    {
        try {
            $filters = $request->all();
            $data = $service->generar($filters, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Reporte general generado exitosamente',
                'data'    => $this->formatResource(ReporteGeneralResource::class, $data),
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_FORBIDDEN);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Endpoint para generar el Reporte Reproductivo.
     *
     * @param Request $request
     * @param ReporteReproductivoService $service
     * @return JsonResponse
     */
    public function reporteReproductivo(Request $request, ReporteReproductivoService $service): JsonResponse
    {
        try {
            $filters = $request->all();
            $data = $service->generar($filters, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Reporte reproductivo generado exitosamente',
                'data'    => $this->formatResource(ReporteReproductivoResource::class, $data),
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_FORBIDDEN);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Endpoint para generar el Reporte de Pesaje de Leche.
     *
     * @param Request $request
     * @param ReportePesajeLecheService $service
     * @return JsonResponse
     */
    public function reportePesajeLeche(Request $request, ReportePesajeLecheService $service): JsonResponse
    {
        try {
            $filters = $request->all();
            $data = $service->generar($filters, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Reporte de pesaje de leche generado exitosamente',
                'data'    => $this->formatResource(ReportePesajeLecheResource::class, $data),
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_FORBIDDEN);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
