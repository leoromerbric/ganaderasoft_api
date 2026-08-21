<?php

namespace App\Services\Reportes;

use App\Models\User;
use App\Services\BaseService;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Servicio para la generación del Reporte de Pesaje de Leche y Rendimiento Productivo.
 */
class ReportePesajeLecheService extends BaseService
{
    /**
     * Genera la estructura de datos para el Reporte de Pesaje de Leche.
     *
     * @param array $filters Filtros opcionales (fecha_inicio, fecha_fin, finca_id, rebano_id, etc.)
     * @param User $user Usuario autenticado que solicita el reporte.
     * @return array Estructura con KPIs lecheros y detalle de ordeños.
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
                'produccion_total_ordeno' => 0.0,
                'promedio_diario_vaca'    => 0.0,
                'vacas_en_ordeno'         => 0,
            ],
            'items' => [
                // Arreglo de filas para la tabla del reporte:
                // [
                //     'fecha_pesaje'          => '2026-08-14',
                //     'rebano_nombre'         => 'Lote Alta Producción',
                //     'ordeno_manana_litros'  => 420.5,
                //     'ordeno_tarde_litros'   => 340.0,
                //     'total_dia_litros'      => 760.5,
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
