<?php

namespace App\Services\Reportes;

use App\Models\Animal;
use App\Models\Diagnostico;
use App\Models\Finca;
use App\Models\ReproduccionAnimal;
use App\Models\ServicioAnimal;
use App\Models\User;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

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
     * @throws ModelNotFoundException
     */
    public function generar(array $filters, User $user): array
    {
        if ($user->cannot('readAny', self::class)) {
            throw new AuthorizationException('Sin permisos para ver reportes.');
        }

        $fechaInicio = $filters['fecha_inicio'] ?? null;
        $fechaFin    = $filters['fecha_fin'] ?? null;
        $fincaId     = $filters['finca_id'] ?? ($filters['id_finca'] ?? null);

        $fincasQuery = Finca::where('archivado', false);
        $this->applyFincaFilter($fincasQuery, $user, null, 'id');

        if ($fincaId) {
            $fincaId = (int) $fincaId;
            if (!$this->checkFincaAccess($user, $fincaId)) {
                throw new AuthorizationException('No tiene permisos para ver reportes de esta finca.');
            }
            $fincasQuery->where('id', $fincaId);
        }

        $fincas = $fincasQuery->get();
        if ($fincas->isEmpty()) {
            throw new ModelNotFoundException('No se encontraron fincas para el reporte.');
        }

        $fincaIds = $fincas->pluck('id')->toArray();
        $primeraFinca = $fincas->first();

        // 1. Animales
        $animalesQuery = Animal::query()
            ->whereHas('rebano', function ($q) use ($fincaIds) {
                $q->whereIn('finca_id', $fincaIds);
            })
            ->where('archivado', false)
            ->with(['rebano.finca', 'etapaActual.etapa']);

        if (!empty($filters['rebano_id'])) {
            $animalesQuery->where('rebano_id', $filters['rebano_id']);
        }
        if (!empty($filters['animal_id'])) {
            $animalesQuery->where('id', $filters['animal_id']);
        }

        $animales = $animalesQuery->orderBy('nombre')->get();
        $animalIds = $animales->pluck('id')->toArray();

        // 2. Partos (ReproduccionAnimal)
        $partos = ReproduccionAnimal::whereHas('etapaAnimal', function ($q) use ($animalIds) {
            $q->whereIn('animal_id', $animalIds);
        })
        ->with('etapaAnimal')
        ->when($fechaInicio, fn($q) => $q->where('fecha_reproduccion', '>=', $fechaInicio))
        ->when($fechaFin, fn($q) => $q->where('fecha_reproduccion', '<=', $fechaFin))
        ->orderBy('fecha_reproduccion', 'desc')
        ->get();

        $partosPorAnimal = [];
        foreach ($partos as $p) {
            $aId = $p->etapaAnimal?->animal_id;
            if ($aId) {
                $partosPorAnimal[$aId][] = $p;
            }
        }

        // 3. Servicios (ServicioAnimal)
        $servicios = ServicioAnimal::whereIn('animal_id', $animalIds)
            ->when($fechaInicio, fn($q) => $q->where('fecha', '>=', $fechaInicio))
            ->when($fechaFin, fn($q) => $q->where('fecha', '<=', $fechaFin))
            ->orderBy('fecha', 'desc')
            ->get();

        $serviciosPorAnimal = [];
        foreach ($servicios as $s) {
            $serviciosPorAnimal[$s->animal_id][] = $s;
        }

        // 4. Diagnósticos / Palpaciones
        $diagnosticos = Diagnostico::whereHas('etapaAnimal', function ($q) use ($animalIds) {
            $q->whereIn('animal_id', $animalIds);
        })
        ->with('etapaAnimal')
        ->when($fechaInicio, fn($q) => $q->where('fecha', '>=', $fechaInicio))
        ->when($fechaFin, fn($q) => $q->where('fecha', '<=', $fechaFin))
        ->orderBy('fecha', 'desc')
        ->get();

        $diagnosticosPorAnimal = [];
        foreach ($diagnosticos as $d) {
            $aId = $d->etapaAnimal?->animal_id;
            if ($aId) {
                $diagnosticosPorAnimal[$aId][] = $d;
            }
        }

        // 5. Consolidar eventos por animal
        $animalesResultado = [];
        $itemsTabla = [];
        $gestacionesConfirmadas = 0;
        $proximosPartos = 0;
        $totalEventosGlobal = 0;

        foreach ($animales as $animal) {
            $misPartos = $partosPorAnimal[$animal->id] ?? [];
            $misServicios = $serviciosPorAnimal[$animal->id] ?? [];
            $misDiagnosticos = $diagnosticosPorAnimal[$animal->id] ?? [];

            $eventos = [];

            foreach ($misPartos as $p) {
                $fStr = $p->fecha_reproduccion ? (is_string($p->fecha_reproduccion) ? substr($p->fecha_reproduccion, 0, 10) : $p->fecha_reproduccion->format('Y-m-d')) : null;
                $eventos[] = [
                    'id'          => $p->id,
                    'origen'      => 'Parto',
                    'tipo'        => $p->tipo_reproduccion ?? 'Normal',
                    'fecha'       => $fStr,
                    'observacion' => $p->observacion ?? 'Parto registrado',
                ];
            }

            foreach ($misServicios as $s) {
                $fStr = $s->fecha ? (is_string($s->fecha) ? substr($s->fecha, 0, 10) : $s->fecha->format('Y-m-d')) : null;
                $eventos[] = [
                    'id'          => $s->id,
                    'origen'      => 'Servicio',
                    'tipo'        => $s->tipo ?? 'Servicio',
                    'fecha'       => $fStr,
                    'observacion' => $s->observacion ?? 'Servicio registrado',
                ];
            }

            foreach ($misDiagnosticos as $d) {
                $fStr = $d->fecha ? (is_string($d->fecha) ? substr($d->fecha, 0, 10) : $d->fecha->format('Y-m-d')) : null;
                $eventos[] = [
                    'id'          => $d->id,
                    'origen'      => 'Palpacion',
                    'tipo'        => $d->tipo ?? 'Diagnóstico',
                    'fecha'       => $fStr,
                    'observacion' => $d->descripcion ?? 'Palpación / Diagnóstico',
                ];
            }

            // Ordenar eventos por fecha descendente
            usort($eventos, fn($a, $b) => strcmp((string)($b['fecha'] ?? ''), (string)($a['fecha'] ?? '')));

            $totalEventos = count($eventos);
            $totalEventosGlobal += $totalEventos;

            $ultimoServicio = !empty($misServicios) ? $misServicios[0] : null;
            $ultimoDiagnostico = !empty($misDiagnosticos) ? $misDiagnosticos[0] : null;

            // Calcular fecha probable de parto y gestación
            $fechaProbableParto = null;
            $diasGestacion = 0;
            $esGestante = false;

            if ($ultimoDiagnostico && stripos((string) $ultimoDiagnostico->tipo, 'gest') !== false) {
                $esGestante = true;
                $gestacionesConfirmadas++;
            }

            if ($ultimoServicio && $ultimoServicio->fecha) {
                $fServ = is_string($ultimoServicio->fecha) ? Carbon::parse($ultimoServicio->fecha) : $ultimoServicio->fecha;
                $fPartoProb = (clone $fServ)->addDays(283);
                $fechaProbableParto = $fPartoProb->format('Y-m-d');
                $diasGestacion = (int) $fServ->diffInDays(now());

                if ($fPartoProb->isBetween(now(), now()->addDays(30))) {
                    $proximosPartos++;
                }
            }

            $animalesResultado[] = [
                'id'            => $animal->id,
                'codigo'        => $animal->codigo_animal ?? (string) $animal->id,
                'nombre'        => $animal->nombre,
                'total_eventos' => $totalEventos,
                'eventos'       => $eventos,
            ];

            if ($totalEventos > 0 || $ultimoServicio) {
                $prioridad = 'Normal';
                if ($diasGestacion >= 260) {
                    $prioridad = 'Inminente';
                } elseif ($diasGestacion >= 240) {
                    $prioridad = 'Atención';
                }

                $itemsTabla[] = [
                    'animal_identificador'  => ($animal->codigo_animal ?? 'ID:' . $animal->id) . ' (' . ($animal->nombre ?? 'Sin nombre') . ')',
                    'ultimo_servicio_fecha' => $ultimoServicio && $ultimoServicio->fecha ? (is_string($ultimoServicio->fecha) ? substr($ultimoServicio->fecha, 0, 10) : $ultimoServicio->fecha->format('Y-m-d')) : 'Sin servicios',
                    'tipo_servicio'         => $ultimoServicio ? ($ultimoServicio->tipo ?? 'Servicio') : '-',
                    'diagnostico_palpacion' => $esGestante ? 'Gestante' : ($ultimoDiagnostico ? $ultimoDiagnostico->tipo : 'Pendiente palpación'),
                    'fecha_probable_parto'  => $fechaProbableParto ?? 'Por confirmar',
                    'dias_gestacion'        => $diasGestacion,
                    'fecha_secado'          => $fechaProbableParto ? Carbon::parse($fechaProbableParto)->subDays(60)->format('Y-m-d') : null,
                    'prioridad'             => $prioridad,
                ];
            }
        }

        $totalServicios = $servicios->count();
        $totalPartos = $partos->count();
        $tasaConcepcion = $totalServicios > 0 ? round(($gestacionesConfirmadas / $totalServicios) * 100, 1) : 0.0;

        return [
            'finca' => [
                'id'     => $primeraFinca->id,
                'nombre' => $primeraFinca->nombre,
            ],
            'resumen' => [
                'total_animales'          => count($animalesResultado),
                'total_eventos'           => $totalEventosGlobal,
                'total_partos'            => $totalPartos,
                'total_servicios'         => $totalServicios,
                'tasa_concepcion'         => $tasaConcepcion,
                'gestaciones_confirmadas' => $gestacionesConfirmadas,
                'proximos_partos'         => $proximosPartos,
            ],
            'kpis' => [
                'tasa_concepcion'         => $tasaConcepcion,
                'gestaciones_confirmadas' => $gestacionesConfirmadas,
                'proximos_partos'         => $proximosPartos,
                'total_partos'            => $totalPartos,
                'total_servicios'         => $totalServicios,
            ],
            'animales'          => $animalesResultado,
            'items'             => $itemsTabla,
            'filtros_aplicados' => [
                'fecha_inicio' => $fechaInicio,
                'fecha_fin'    => $fechaFin,
                'finca_id'     => $fincaId,
                'rebano_id'    => $filters['rebano_id'] ?? null,
                'animal_id'    => $filters['animal_id'] ?? null,
            ],
        ];
    }
}
