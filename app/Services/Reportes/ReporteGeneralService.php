<?php

namespace App\Services\Reportes;

use App\Models\User;
use App\Services\BaseService;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Servicio para la generación del Reporte General de Finca / Inventario Ganadero.
 */
class ReporteGeneralService extends BaseService
{
    /**
     * Genera la estructura de datos para el Reporte General.
     *
     * @param array $filters Filtros opcionales (fecha_inicio, fecha_fin, finca_id, rebano_id, etc.)
     * @param User $user Usuario autenticado que solicita el reporte.
     * @return array Estructura con KPIs y detalle consolidado de animales/fincas.
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
                'total_animales'      => 0,
                'rebanos_activos'     => 0,
                'personal_registrado' => 0,
            ],
            'items' => [
                // Arreglo de filas para la tabla del reporte:
                // [
                //     'finca_nombre'       => 'Finca El Paraíso',
                //     'categoria'          => 'Vacas en ordeño',
                //     'cantidad_animales'  => 45,
                //     'estado_nutricional' => 'Excelente',
                //     'observacion'        => 'Sin novedad sanitaria',
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
