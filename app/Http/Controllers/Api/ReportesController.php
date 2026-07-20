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
    protected $reportesService;

    public function __construct(ReportesService $reportesService)
    {
        $this->reportesService = $reportesService;
        $this->middleware(\App\Http\Middleware\Legacy\Reportes\NormalizeEstadisticasFincas::class)->only('estadisticasFincas');
    }

    /**
     * Get statistical reports for farms (fincas).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function estadisticasFincas(Request $request)
    {
        
            $filters = $request->only(['propietario_id', 'finca_id']);
            $estadisticas = $this->reportesService->getEstadisticasFincas($filters, $request->user());

            return response()->json(new EstadisticasFincasResource($estadisticas));
            throw $e; return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_FORBIDDEN);
    }
}
