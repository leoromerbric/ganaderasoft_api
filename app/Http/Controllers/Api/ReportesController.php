<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Animal;
use App\Models\Finca;
use App\Models\PersonalFinca;
use App\Models\Propietario;
use App\Models\Rebano;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class ReportesController extends Controller
{
    /**
     * Get statistical reports for farms (fincas).
     * Returns consolidated metrics for all farms of a propietario or a specific farm.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function estadisticasFincas(Request $request)
    {
        $user = $request->user();

        // Get propietario for the user
        $propietario = null;
        if ($user->isAdmin()) {
            // Admin can query any propietario
            $propietarioId = $request->query('id_propietario');
            if ($propietarioId) {
                $propietario = Propietario::find($propietarioId);
                if (! $propietario) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Propietario no encontrado',
                    ], Response::HTTP_NOT_FOUND);
                }
            }
        } else {
            // Non-admin users can only query their own data
            $propietario = $user->propietario;
            if (! $propietario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no es propietario',
                ], Response::HTTP_FORBIDDEN);
            }
        }

        // Get optional finca filter
        $fincaId = $request->query('id_finca');

        // Build base query for fincas
        $fincasQuery = Finca::active();

        if ($propietario) {
            $fincasQuery->forPropietario($propietario->id);
        }

        if ($fincaId) {
            $fincasQuery->where('id_Finca', $fincaId);
        }

        $fincas = $fincasQuery->get();

        if ($fincas->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontraron fincas',
            ], Response::HTTP_NOT_FOUND);
        }

        $fincaIds = $fincas->pluck('id_Finca');

        // 1. Get farm statistics
        $totalFincas = $fincas->count();

        // 2. Get herds (rebaños) statistics
        $rebanos = Rebano::whereIn('id_Finca', $fincaIds)
            ->active()
            ->get();
        $totalRebanos = $rebanos->count();

        // Get rebanos with animal counts grouped by finca
        $rebanosPorFinca = DB::table('rebano')
            ->select('id_Finca', DB::raw('COUNT(*) as cantidad_rebanos'))
            ->whereIn('id_Finca', $fincaIds)
            ->where('archivado', false)
            ->groupBy('id_Finca')
            ->get()
            ->keyBy('id_Finca');

        // 3. Get animals statistics
        $rebanoIds = $rebanos->pluck('id_Rebano');

        $totalAnimales = Animal::whereIn('id_Rebano', $rebanoIds)
            ->active()
            ->count();

        // Animals by sex
        $animalesPorSexo = Animal::whereIn('id_Rebano', $rebanoIds)
            ->active()
            ->select('Sexo', DB::raw('COUNT(*) as cantidad'))
            ->groupBy('Sexo')
            ->get()
            ->pluck('cantidad', 'Sexo')
            ->toArray();

        // Animals per herd
        $animalesPorRebano = Animal::whereIn('id_Rebano', $rebanoIds)
            ->active()
            ->select('id_Rebano', DB::raw('COUNT(*) as cantidad_animales'))
            ->groupBy('id_Rebano')
            ->get()
            ->keyBy('id_Rebano');

        // Animals per finca
        $animalesPorFinca = DB::table('animal')
            ->join('rebano', 'animal.id_Rebano', '=', 'rebano.id_Rebano')
            ->select('rebano.id_Finca', DB::raw('COUNT(*) as cantidad_animales'))
            ->whereIn('rebano.id_Finca', $fincaIds)
            ->where('animal.archivado', false)
            ->where('rebano.archivado', false)
            ->groupBy('rebano.id_Finca')
            ->get()
            ->keyBy('id_Finca');

        // 4. Get personnel statistics
        $totalPersonal = PersonalFinca::whereIn('id_Finca', $fincaIds)
            ->count();

        // Personnel by worker type
        $personalPorTipo = PersonalFinca::whereIn('id_Finca', $fincaIds)
            ->select('Tipo_Trabajador', DB::raw('COUNT(*) as cantidad'))
            ->groupBy('Tipo_Trabajador')
            ->get()
            ->pluck('cantidad', 'Tipo_Trabajador')
            ->toArray();

        // Personnel per finca
        $personalPorFinca = PersonalFinca::whereIn('id_Finca', $fincaIds)
            ->select('id_Finca', DB::raw('COUNT(*) as cantidad_personal'))
            ->groupBy('id_Finca')
            ->get()
            ->keyBy('id_Finca');

        // Build detailed statistics per finca
        $fincasDetalle = [];
        foreach ($fincas as $finca) {
            $fincaId = $finca->id_Finca;
            $fincasDetalle[] = [
                'id_Finca' => $fincaId,
                'Nombre' => $finca->Nombre,
                'cantidad_rebanos' => $rebanosPorFinca->get($fincaId)->cantidad_rebanos ?? 0,
                'cantidad_animales' => $animalesPorFinca->get($fincaId)->cantidad_animales ?? 0,
                'cantidad_personal' => $personalPorFinca->get($fincaId)->cantidad_personal ?? 0,
            ];
        }

        // Build detailed statistics per rebano
        $rebanosDetalle = [];
        foreach ($rebanos as $rebano) {
            $rebanoId = $rebano->id_Rebano;
            $rebanosDetalle[] = [
                'id_Rebano' => $rebanoId,
                'id_Finca' => $rebano->id_Finca,
                'Nombre' => $rebano->Nombre,
                'cantidad_animales' => $animalesPorRebano->get($rebanoId)->cantidad_animales ?? 0,
            ];
        }

        // Build response
        $estadisticas = [
            'resumen' => [
                'total_fincas' => $totalFincas,
                'total_rebanos' => $totalRebanos,
                'total_animales' => $totalAnimales,
                'total_personal' => $totalPersonal,
            ],
            'animales_por_sexo' => $animalesPorSexo,
            'personal_por_tipo' => $personalPorTipo,
            'fincas' => $fincasDetalle,
            'rebanos' => $rebanosDetalle,
        ];

        return response()->json([
            'success' => true,
            'message' => 'Estadísticas de fincas',
            'data' => $estadisticas,
        ]);
    }
}
