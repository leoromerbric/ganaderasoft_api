<?php

namespace App\Services\Reportes;

use App\Models\User;
use App\Services\BaseService;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Servicio para la generación del Reporte Reproductivo (celos, servicios, gestaciones, partos).
 */
class ReporteReproductivoService extends BaseService
{
    /**
     * Genera la estructura de datos para el Reporte Reproductivo.
     *
     * @param array $filters Filtros opcionales (fecha_inicio, fecha_fin, finca_id, animal_id, etc.)
     * @param User $user Usuario autenticado que solicita el reporte.
     * @return array Estructura con KPIs reproductivos y detalle de servicios/palpaciones.
     *
     * @throws AuthorizationException
     */
    public function generar(array $filters, User $user): array
    {
        if (!$user->hasPermissionTo('reportes.read')) {
            throw new AuthorizationException('Sin permisos para ver reportes.');
        }

        $fechaInicio = $filters['fecha_inicio'] ?? null;
        $fechaFin    = $filters['fecha_fin'] ?? null;
        $fincaId     = $filters['finca_id'] ?? null;

        // TODO: El equipo debe implementar las consultas específicas de agregación aquí.
        // Ejemplo de estructura de retorno esperada por el frontend:
        return [
            'kpis' => [
                'tasa_concepcion'         => 0.0,
                'gestaciones_confirmadas' => 0,
                'proximos_partos'         => 0,
            ],
            'items' => [
                // Arreglo de filas para la tabla del reporte:
                // [
                //     'animal_identificador'  => 'Vaca #104 (Mariposa)',
                //     'ultimo_servicio_fecha' => '2026-05-12',
                //     'tipo_servicio'         => 'IA (Semen Holstein)',
                //     'diagnostico_palpacion' => 'Gestante (3m)',
                //     'fecha_probable_parto'  => '2027-02-18',
                // ],
            ],
            'filtros_aplicados' => [
                'fecha_inicio' => $fechaInicio,
                'fecha_fin'    => $fechaFin,
                'finca_id'     => $fincaId,
            ],
        ];
    }
}
