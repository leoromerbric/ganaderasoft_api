<?php

namespace App\Services\Reportes;

use App\Models\Animal;
use App\Models\Finca;
use App\Models\Lactancia;
use App\Models\Leche;
use App\Models\PersonalFinca;
use App\Models\PesoCorporal;
use App\Models\Rebano;
use App\Models\ReproduccionAnimal;
use App\Models\ServicioAnimal;
use App\Models\User;
use App\Services\BaseService;
use DateTime;
use DateTimeInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class ReportesService extends BaseService
{
    /**
     * Get statistical reports for farms (fincas).
     *
     * @param array $filters
     * @param User $user
     * @return array
     * @throws AuthorizationException
     * @throws ModelNotFoundException
     */
    public function getEstadisticasFincas(array $filters, User $user): array
    {
        if ($user->cannot('readAny', self::class)) {
            throw new AuthorizationException('Sin permisos para ver reportes.');
        }

        $fincasQuery = Finca::where('archivado', false);

        // Si se pasa un filtro explícito de propietario (útil para administradores)
        if (!empty($filters['propietario_id'])) {
            $fincasQuery->where('propietario_id', $filters['propietario_id']);
        }

        // Aplica el filtro de base de datos para que el usuario solo vea estadísticas
        // de las fincas a las que tiene acceso (como admin, propietario o trabajador)
        $this->applyFincaFilter($fincasQuery, $user, null, 'id');

        if (isset($filters['finca_id'])) {
            $fincasQuery->where('id', $filters['finca_id']);
        }

        $fincas = $fincasQuery->get();

        if ($fincas->isEmpty()) {
            throw new ModelNotFoundException('No se encontraron fincas');
        }

        $fincaIds = $fincas->pluck('id')->toArray();

        // 1. Total Fincas
        $totalFincas = $fincas->count();

        // 2. Rebaños
        $rebanos = Rebano::whereIn('finca_id', $fincaIds)
            ->where('archivado', false)
            ->get();
        $totalRebanos = $rebanos->count();
        $rebanoIds = $rebanos->pluck('id')->toArray();

        $rebanosPorFinca = Rebano::whereIn('finca_id', $fincaIds)
            ->where('archivado', false)
            ->select('finca_id', DB::raw('COUNT(*) as cantidad_rebanos'))
            ->groupBy('finca_id')
            ->pluck('cantidad_rebanos', 'finca_id');

        // 3. Animales
        $totalAnimales = Animal::whereIn('rebano_id', $rebanoIds)
            ->where('archivado', false)
            ->count();

        $animalesPorSexo = Animal::whereIn('rebano_id', $rebanoIds)
            ->where('archivado', false)
            ->select('sexo', DB::raw('COUNT(*) as cantidad'))
            ->groupBy('sexo')
            ->pluck('cantidad', 'sexo')
            ->toArray();

        $animalesPorRebano = Animal::whereIn('rebano_id', $rebanoIds)
            ->where('archivado', false)
            ->select('rebano_id', DB::raw('COUNT(*) as cantidad_animales'))
            ->groupBy('rebano_id')
            ->pluck('cantidad_animales', 'rebano_id');

        $animalesPorFinca = Animal::join('rebanos', 'animals.rebano_id', '=', 'rebanos.id')
            ->whereIn('rebanos.finca_id', $fincaIds)
            ->where('animals.archivado', false)
            ->where('rebanos.archivado', false)
            ->select('rebanos.finca_id', DB::raw('COUNT(*) as cantidad_animales'))
            ->groupBy('rebanos.finca_id')
            ->pluck('cantidad_animales', 'finca_id');

        // 4. Personal
        $totalPersonal = PersonalFinca::whereIn('finca_id', $fincaIds)->count();

        $personalPorTipo = PersonalFinca::whereIn('finca_id', $fincaIds)
            ->select('tipo_trabajador_id', DB::raw('COUNT(*) as cantidad'))
            ->groupBy('tipo_trabajador_id')
            ->pluck('cantidad', 'tipo_trabajador_id')
            ->toArray();

        $personalPorFinca = PersonalFinca::whereIn('finca_id', $fincaIds)
            ->select('finca_id', DB::raw('COUNT(*) as cantidad_personal'))
            ->groupBy('finca_id')
            ->pluck('cantidad_personal', 'finca_id');

        // Detalles
        $fincasDetalle = $fincas->map(function ($finca) use ($rebanosPorFinca, $animalesPorFinca, $personalPorFinca) {
            return [
                'finca_id' => $finca->id,
                'nombre' => $finca->nombre,
                'cantidad_rebanos' => $rebanosPorFinca[$finca->id] ?? 0,
                'cantidad_animales' => $animalesPorFinca[$finca->id] ?? 0,
                'cantidad_personal' => $personalPorFinca[$finca->id] ?? 0,
            ];
        })->values()->toArray();

        $rebanosDetalle = $rebanos->map(function ($rebano) use ($animalesPorRebano) {
            return [
                'rebano_id' => $rebano->id,
                'finca_id' => $rebano->finca_id,
                'nombre' => $rebano->nombre,
                'cantidad_animales' => $animalesPorRebano[$rebano->id] ?? 0,
            ];
        })->values()->toArray();

        return [
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
    }

    /**
     * Reporte de Datos Generales de la Finca (Animales, categorías, edades, pesos y genealogía).
     *
     * @param array $filters
     * @param User $user
     * @return array
     * @throws AuthorizationException
     * @throws ModelNotFoundException
     */
    public function getReporteGeneral(array $filters, User $user): array
    {
        if ($user->cannot('readAny', self::class)) {
            throw new AuthorizationException('Sin permisos para ver reportes.');
        }

        $fincaId = $this->resolveFincaId($filters, $user);
        $finca = Finca::findOrFail($fincaId);

        $query = Animal::query()
            ->whereHas('rebano', function ($q) use ($fincaId) {
                $q->where('finca_id', $fincaId);
            })
            ->with([
                'rebano',
                'composicionRaza',
                'etapaActual.etapa',
                'registroPadre.padre',
                'registroMadre.padre',
            ]);

        if (!empty($filters['rebano_id'])) {
            $query->where('rebano_id', $filters['rebano_id']);
        }

        if (isset($filters['archivado'])) {
            if ($filters['archivado'] === 'true' || $filters['archivado'] === true || $filters['archivado'] === '1' || $filters['archivado'] === 1) {
                $query->where('archivado', true);
            } elseif ($filters['archivado'] === 'todos') {
                // Sin filtro de archivado
            } else {
                $query->where('archivado', false);
            }
        } else {
            $query->where('archivado', false);
        }

        $animales = $query->orderBy('nombre')->get();
        $animalIds = $animales->pluck('id')->toArray();

        // Obtener todos los pesos corporales para estos animales indexados por animal_id
        $pesosPorAnimal = [];
        if (!empty($animalIds)) {
            $pesos = PesoCorporal::whereHas('etapaAnimal', function ($q) use ($animalIds) {
                $q->whereIn('animal_id', $animalIds);
            })->with('etapaAnimal')->orderBy('fecha_peso', 'asc')->get();

            foreach ($pesos as $peso) {
                $anId = $peso->etapaAnimal?->animal_id ?? $peso->animal_etapa_id;
                if ($anId) {
                    $pesosPorAnimal[$anId][] = $peso;
                }
            }
        }

        $reporteAnimales = $animales->map(function ($animal) use ($pesosPorAnimal) {
            $animalPesos = $pesosPorAnimal[$animal->id] ?? [];
            $totalPesos = count($animalPesos);

            $primerPeso = $totalPesos > 0 ? $animalPesos[0] : null;
            $ultimoPeso = $totalPesos > 0 ? $animalPesos[$totalPesos - 1] : null;
            $penultimoPeso = $totalPesos > 1 ? $animalPesos[$totalPesos - 2] : null;

            return [
                'id'                   => $animal->id,
                'codigo'               => $animal->codigo_animal,
                'nombre'               => $animal->nombre,
                'sexo'                 => $animal->sexo,
                'categoria'            => $animal->etapaActual?->etapa?->nombre ?? 'S/C',
                'estatus'              => $animal->archivado ? 'Archivado' : 'Activo',
                'archivado'            => $animal->archivado,
                'rebano_id'            => $animal->rebano_id,
                'rebano_nombre'        => $animal->rebano?->nombre ?? 'Sin rebaño',
                'fecha_nacimiento'     => $animal->fecha_nacimiento ? $animal->fecha_nacimiento->format('Y-m-d') : null,
                'edad_meses'           => $animal->edad_meses ?? 0,
                'edad_formateada'      => $animal->edad_formateada,
                'raza'                 => $animal->composicionRaza?->nombre ?? $animal->composicionRaza?->tipo_raza ?? 'S/R',
                'peso_ingreso'         => $primerPeso ? (float) $primerPeso->peso : null,
                'fecha_ingreso'        => $primerPeso && $primerPeso->fecha_peso ? $primerPeso->fecha_peso->format('Y-m-d') : null,
                'penultimo_peso'       => $penultimoPeso ? (float) $penultimoPeso->peso : null,
                'fecha_penultimo_peso' => $penultimoPeso && $penultimoPeso->fecha_peso ? $penultimoPeso->fecha_peso->format('Y-m-d') : null,
                'ultimo_peso'          => $ultimoPeso ? (float) $ultimoPeso->peso : null,
                'fecha_ultimo_peso'    => $ultimoPeso && $ultimoPeso->fecha_peso ? $ultimoPeso->fecha_peso->format('Y-m-d') : null,
                'padre_id'             => $animal->registroPadre?->padre_id,
                'padre_codigo'         => $animal->registroPadre?->padre?->codigo_animal ?? $animal->registroPadre?->padre?->nombre,
                'madre_id'             => $animal->registroMadre?->padre_id,
                'madre_codigo'         => $animal->registroMadre?->padre?->codigo_animal ?? $animal->registroMadre?->padre?->nombre,
            ];
        })->values()->toArray();

        return [
            'finca' => [
                'id'               => $finca->id,
                'nombre'           => $finca->nombre,
                'explotacion_tipo' => $finca->explotacion_tipo,
                'propietario_id'   => $finca->propietario_id,
            ],
            'total_animales' => count($reporteAnimales),
            'animales'       => $reporteAnimales,
        ];
    }

    /**
     * Reporte de Historia de Lactancias con cálculo matemático del Test Interval Method (TIM).
     *
     * @param array $filters
     * @param User $user
     * @return array
     * @throws AuthorizationException
     * @throws ModelNotFoundException
     */
    public function getReporteLactancias(array $filters, User $user): array
    {
        if ($user->cannot('readAny', self::class)) {
            throw new AuthorizationException('Sin permisos para ver reportes.');
        }

        $fincaId = $this->resolveFincaId($filters, $user);
        $finca = Finca::findOrFail($fincaId);

        $animalesQuery = Animal::query()
            ->whereHas('rebano', function ($q) use ($fincaId) {
                $q->where('finca_id', $fincaId);
            })
            ->whereIn('sexo', ['H', 'F', 'HEMBRA', 'Hembra', 'hembra'])
            ->with(['rebano', 'etapaActual.etapa']);

        if (!empty($filters['rebano_id'])) {
            $animalesQuery->where('rebano_id', $filters['rebano_id']);
        }

        if (!empty($filters['animal_id'])) {
            $animalesQuery->where('id', $filters['animal_id']);
        }

        $animales = $animalesQuery->orderBy('nombre')->get();
        $animalIds = $animales->pluck('id')->toArray();

        // Obtener todas las lactancias con sus pesajes de leche
        $lactancias = Lactancia::whereHas('etapaAnimal', function ($q) use ($animalIds) {
            $q->whereIn('animal_id', $animalIds);
        })
        ->with([
            'etapaAnimal',
            'lecheRecords' => function ($q) {
                $q->orderBy('fecha_pesaje', 'asc');
            }
        ])
        ->orderBy('fecha_inicio', 'asc')
        ->get();

        $lactanciasPorAnimal = [];
        foreach ($lactancias as $lact) {
            $anId = $lact->etapaAnimal?->animal_id;
            if ($anId) {
                $lactanciasPorAnimal[$anId][] = $lact;
            }
        }

        $resultadoAnimales = [];
        $totalProduccionFinca = 0.0;

        foreach ($animales as $animal) {
            $animalLacts = $lactanciasPorAnimal[$animal->id] ?? [];
            $listaLactancias = [];
            $produccionVitalicia = 0.0;
            $nro = 1;

            foreach ($animalLacts as $lact) {
                $inicio = $lact->fecha_inicio;
                $fin = $lact->fecha_fin;

                $diasLactancia = $fin
                    ? $inicio->diffInDays($fin)
                    : ($inicio ? $inicio->diffInDays(now()) : 0);

                $pesajes = $lact->lecheRecords;

                $p244 = $inicio ? $this->calculateTIM($inicio, $fin, $pesajes, 244) : null;
                $p270 = $inicio ? $this->calculateTIM($inicio, $fin, $pesajes, 270) : null;
                $p305 = $inicio ? $this->calculateTIM($inicio, $fin, $pesajes, 305) : null;
                $prodTotal = $inicio ? $this->calculateTIM($inicio, $fin, $pesajes, $diasLactancia) : null;

                if ($prodTotal) {
                    $produccionVitalicia += $prodTotal;
                }

                $listaLactancias[] = [
                    'id'               => $lact->id,
                    'num_lactancia'    => $nro++,
                    'fecha_inicio'     => $inicio ? $inicio->format('Y-m-d') : null,
                    'fecha_fin'        => $fin ? $fin->format('Y-m-d') : null,
                    'estado'           => $fin ? 'Secada' : 'En curso',
                    'dias_lactancia'   => $diasLactancia,
                    'p244'             => $p244,
                    'p270'             => $p270,
                    'p305'             => $p305,
                    'produccion_total' => $prodTotal ? round($prodTotal, 2) : 0.0,
                    'total_pesajes'    => $pesajes->count(),
                ];
            }

            $totalProduccionFinca += $produccionVitalicia;

            $resultadoAnimales[] = [
                'id'                   => $animal->id,
                'codigo'               => $animal->codigo_animal,
                'nombre'               => $animal->nombre,
                'categoria'            => $animal->etapaActual?->etapa?->nombre ?? 'S/C',
                'estatus'              => $animal->archivado ? 'Archivado' : 'Activo',
                'rebano_nombre'        => $animal->rebano?->nombre ?? 'Sin rebaño',
                'total_lactancias'     => count($listaLactancias),
                'produccion_vitalicia' => round($produccionVitalicia, 2),
                'lactancias'           => $listaLactancias,
            ];
        }

        return [
            'finca' => [
                'id'     => $finca->id,
                'nombre' => $finca->nombre,
            ],
            'total_animales'         => count($resultadoAnimales),
            'produccion_total_finca' => round($totalProduccionFinca, 2),
            'animales'               => $resultadoAnimales,
        ];
    }

    /**
     * Reporte de Historial Reproductivo Consolidado (Partos y Servicios de Inseminación/Monta).
     *
     * @param array $filters
     * @param User $user
     * @return array
     * @throws AuthorizationException
     * @throws ModelNotFoundException
     */
    public function getReporteReproductivo(array $filters, User $user): array
    {
        if ($user->cannot('readAny', self::class)) {
            throw new AuthorizationException('Sin permisos para ver reportes.');
        }

        $fincaId = $this->resolveFincaId($filters, $user);
        $finca = Finca::findOrFail($fincaId);

        $animalesQuery = Animal::query()
            ->whereHas('rebano', function ($q) use ($fincaId) {
                $q->where('finca_id', $fincaId);
            })
            ->with(['rebano', 'etapaActual.etapa']);

        if (!empty($filters['rebano_id'])) {
            $animalesQuery->where('rebano_id', $filters['rebano_id']);
        }

        if (!empty($filters['animal_id'])) {
            $animalesQuery->where('id', $filters['animal_id']);
        }

        $animales = $animalesQuery->orderBy('nombre')->get();
        $animalIds = $animales->pluck('id')->toArray();

        // 1. Partos / Reproducción
        $reproducciones = ReproduccionAnimal::whereHas('etapaAnimal', function ($q) use ($animalIds) {
            $q->whereIn('animal_id', $animalIds);
        })
        ->with('etapaAnimal')
        ->when(!empty($filters['fecha_inicio']), function ($q) use ($filters) {
            $q->where('fecha_reproduccion', '>=', $filters['fecha_inicio']);
        })
        ->when(!empty($filters['fecha_fin']), function ($q) use ($filters) {
            $q->where('fecha_reproduccion', '<=', $filters['fecha_fin']);
        })
        ->get();

        // 2. Servicios
        $servicios = ServicioAnimal::whereIn('animal_id', $animalIds)
            ->with(['semen', 'tecnico.persona'])
            ->when(!empty($filters['fecha_inicio']), function ($q) use ($filters) {
                $q->where('fecha', '>=', $filters['fecha_inicio']);
            })
            ->when(!empty($filters['fecha_fin']), function ($q) use ($filters) {
                $q->where('fecha', '<=', $filters['fecha_fin']);
            })
            ->get();

        // Indexar por animal_id
        $eventosPorAnimal = [];

        foreach ($reproducciones as $rep) {
            $anId = $rep->etapaAnimal?->animal_id;
            if ($anId) {
                $eventosPorAnimal[$anId][] = [
                    'id'          => $rep->id,
                    'origen'      => 'Parto',
                    'tipo'        => 'Parto' . ($rep->tipo_reproduccion ? ' - ' . $rep->tipo_reproduccion : ''),
                    'fecha'       => $rep->fecha_reproduccion ? $rep->fecha_reproduccion->format('Y-m-d') : null,
                    'observacion' => $rep->observacion ?? '-',
                ];
            }
        }

        foreach ($servicios as $serv) {
            $tecnicoNombre = $serv->tecnico?->persona
                ? $serv->tecnico->persona->nombre . ' ' . $serv->tecnico->persona->apellido
                : null;

            $eventosPorAnimal[$serv->animal_id][] = [
                'id'          => $serv->id,
                'origen'      => 'Servicio',
                'tipo'        => 'Servicio' . ($serv->tipo ? ' - ' . $serv->tipo : ''),
                'fecha'       => $serv->fecha ? $serv->fecha->format('Y-m-d') : null,
                'observacion' => $serv->observacion ?? '-',
                'semen'       => $serv->semen?->codigo ?? $serv->semen?->toro_nombre,
                'tecnico'     => $tecnicoNombre,
            ];
        }

        $totalEventos = 0;
        $totalPartos = 0;
        $totalServicios = 0;

        $resultadoAnimales = [];

        foreach ($animales as $animal) {
            $eventos = $eventosPorAnimal[$animal->id] ?? [];

            // Ordenar cronológicamente descendente
            usort($eventos, function ($a, $b) {
                return strcmp($b['fecha'] ?? '', $a['fecha'] ?? '');
            });

            foreach ($eventos as $ev) {
                $totalEventos++;
                if ($ev['origen'] === 'Parto') {
                    $totalPartos++;
                } elseif ($ev['origen'] === 'Servicio') {
                    $totalServicios++;
                }
            }

            $resultadoAnimales[] = [
                'id'             => $animal->id,
                'codigo'         => $animal->codigo_animal,
                'nombre'         => $animal->nombre,
                'categoria'      => $animal->etapaActual?->etapa?->nombre ?? 'S/C',
                'estatus'        => $animal->archivado ? 'Archivado' : 'Activo',
                'rebano_nombre'  => $animal->rebano?->nombre ?? 'Sin rebaño',
                'total_eventos'  => count($eventos),
                'eventos'        => $eventos,
            ];
        }

        return [
            'finca' => [
                'id'     => $finca->id,
                'nombre' => $finca->nombre,
            ],
            'resumen' => [
                'total_animales'  => count($resultadoAnimales),
                'total_eventos'   => $totalEventos,
                'total_partos'    => $totalPartos,
                'total_servicios' => $totalServicios,
            ],
            'animales' => $resultadoAnimales,
        ];
    }

    /**
     * Reporte Histórico y Detallado de Pesajes de Leche de la Finca.
     *
     * @param array $filters
     * @param User $user
     * @return array
     * @throws AuthorizationException
     * @throws ModelNotFoundException
     */
    public function getReportePesajeLeche(array $filters, User $user): array
    {
        if ($user->cannot('readAny', self::class)) {
            throw new AuthorizationException('Sin permisos para ver reportes.');
        }

        $fincaId = $this->resolveFincaId($filters, $user);
        $finca = Finca::findOrFail($fincaId);

        $query = Leche::whereHas('lactancia.etapaAnimal.animal.rebano', function ($q) use ($fincaId) {
            $q->where('finca_id', $fincaId);
        })
        ->with([
            'lactancia.etapaAnimal.animal.rebano',
            'lactancia.etapa',
        ]);

        if (!empty($filters['rebano_id'])) {
            $query->whereHas('lactancia.etapaAnimal.animal', function ($q) use ($filters) {
                $q->where('rebano_id', $filters['rebano_id']);
            });
        }

        if (!empty($filters['animal_id'])) {
            $query->whereHas('lactancia.etapaAnimal', function ($q) use ($filters) {
                $q->where('animal_id', $filters['animal_id']);
            });
        }

        if (!empty($filters['fecha_inicio'])) {
            $query->where('fecha_pesaje', '>=', $filters['fecha_inicio']);
        }

        if (!empty($filters['fecha_fin'])) {
            $query->where('fecha_pesaje', '<=', $filters['fecha_fin']);
        }

        $pesajes = $query->orderBy('fecha_pesaje', 'desc')->get();

        $pesajesList = [];
        $totalKg = 0.0;

        foreach ($pesajes as $ps) {
            $animal = $ps->lactancia?->etapaAnimal?->animal;
            $peso = (float) $ps->pesaje_total;
            $totalKg += $peso;

            $pesajesList[] = [
                'id'            => $ps->id,
                'codigo'        => $animal?->codigo_animal ?? 'S/C',
                'nombre'        => $animal?->nombre ?? 'S/N',
                'categoria'     => $ps->lactancia?->etapa?->nombre ?? 'Lactancia',
                'estatus'       => $animal && $animal->archivado ? 'Archivado' : 'Activo',
                'lote'          => $animal?->rebano?->nombre ?? 'Sin rebaño',
                'fecha_evento'  => $ps->fecha_pesaje ? $ps->fecha_pesaje->format('Y-m-d') : null,
                'lactancia_id'  => $ps->lactancia_id,
                'peso_total'    => round($peso, 2),
            ];
        }

        $promedioDiario = count($pesajesList) > 0 ? round($totalKg / count($pesajesList), 2) : 0.0;

        return [
            'finca' => [
                'id'     => $finca->id,
                'nombre' => $finca->nombre,
            ],
            'resumen' => [
                'total_pesajes'    => count($pesajesList),
                'total_produccion' => round($totalKg, 2),
                'promedio_pesaje'  => $promedioDiario,
            ],
            'pesajes' => $pesajesList,
        ];
    }

    /**
     * Reporte / Resumen de Rebaños de la Finca.
     *
     * @param array $filters
     * @param User $user
     * @return array
     * @throws AuthorizationException
     * @throws ModelNotFoundException
     */
    public function getReporteRebanos(array $filters, User $user): array
    {
        if ($user->cannot('readAny', self::class)) {
            throw new AuthorizationException('Sin permisos para ver reportes.');
        }

        $fincaId = $this->resolveFincaId($filters, $user);
        $finca = Finca::findOrFail($fincaId);

        $rebanos = Rebano::where('finca_id', $fincaId)
            ->withCount([
                'animals as total_animales',
                'animals as animales_activos' => function ($q) {
                    $q->where('archivado', false);
                },
                'animals as animales_archivados' => function ($q) {
                    $q->where('archivado', true);
                },
                'animals as machos' => function ($q) {
                    $q->whereIn('sexo', ['M', 'MACHO', 'Macho', 'macho'])->where('archivado', false);
                },
                'animals as hembras' => function ($q) {
                    $q->whereIn('sexo', ['H', 'F', 'HEMBRA', 'Hembra', 'hembra'])->where('archivado', false);
                },
            ])
            ->orderBy('nombre')
            ->get();

        return [
            'finca' => [
                'id'     => $finca->id,
                'nombre' => $finca->nombre,
            ],
            'total_rebanos' => $rebanos->count(),
            'rebanos'       => $rebanos->map(function ($r) {
                return [
                    'id'                  => $r->id,
                    'nombre'              => $r->nombre,
                    'archivado'           => (bool) $r->archivado,
                    'created_at'          => $r->created_at ? $r->created_at->format('Y-m-d H:i:s') : null,
                    'total_animales'      => $r->total_animales,
                    'animales_activos'    => $r->animales_activos,
                    'animales_archivados' => $r->animales_archivados,
                    'machos'              => $r->machos,
                    'hembras'             => $r->hembras,
                ];
            })->values()->toArray(),
        ];
    }

    /**
     * Algoritmo Test Interval Method (TIM / Método del Intervalo de Prueba).
     * Interpola linealmente los pesajes de leche para proyectar la producción estándar
     * a 244, 270, 305 días o los días reales de lactancia.
     *
     * @param string|DateTimeInterface $startDate
     * @param string|DateTimeInterface|null $endDate
     * @param \Illuminate\Support\Collection|array $weighings
     * @param int $targetDays
     * @return float|null
     */
    public function calculateTIM($startDate, $endDate, $weighings, int $targetDays): ?float
    {
        $weighings = collect($weighings);
        if ($weighings->isEmpty()) {
            return null;
        }

        $start = new DateTime($startDate instanceof DateTimeInterface ? $startDate->format('Y-m-d') : (string)$startDate);
        $totalYield = 0.0;
        $currentDays = 0;
        $lastDate = clone $start;
        $lastYield = 0.0;

        if ($endDate) {
            $end = new DateTime($endDate instanceof DateTimeInterface ? $endDate->format('Y-m-d') : (string)$endDate);
            $totalDays = $start->diff($end)->days;
            if ($totalDays < $targetDays) {
                $targetDays = $totalDays;
            }
        }

        foreach ($weighings as $index => $w) {
            $rawDate = $w->fecha_pesaje ?? $w['fecha_pesaje'] ?? null;
            $rawYield = $w->pesaje_total ?? $w['pesaje_total'] ?? 0;

            if (!$rawDate) {
                continue;
            }

            $wDate = new DateTime($rawDate instanceof DateTimeInterface ? $rawDate->format('Y-m-d') : (string)$rawDate);
            $wYield = (float) $rawYield;

            $daysSinceStart = $start->diff($wDate)->days;

            if ($daysSinceStart > $targetDays) {
                $allowedIntervalDays = $targetDays - $currentDays;
                if ($index == 0) {
                    $totalYield += $allowedIntervalDays * $wYield;
                } else {
                    $intervalDays = $lastDate->diff($wDate)->days;
                    if ($intervalDays > 0) {
                        $yieldAtTarget = $lastYield + (($wYield - $lastYield) * ($allowedIntervalDays / $intervalDays));
                        $totalYield += (($lastYield + $yieldAtTarget) / 2) * $allowedIntervalDays;
                    }
                }
                $currentDays = $targetDays;
                break;
            }

            $intervalDays = $lastDate->diff($wDate)->days;
            if ($index == 0) {
                $totalYield += $intervalDays * $wYield;
            } else {
                $totalYield += (($lastYield + $wYield) / 2) * $intervalDays;
            }

            $lastDate = $wDate;
            $lastYield = $wYield;
            $currentDays = $daysSinceStart;
        }

        if ($currentDays < $targetDays) {
            $remainingDays = $targetDays - $currentDays;
            $totalYield += $remainingDays * $lastYield;
        }

        return round($totalYield, 2);
    }

    /**
     * Resuelve y valida el ID de finca a partir de filtros y políticas de autorización del usuario.
     *
     * @param array $filters
     * @param User $user
     * @return int
     * @throws AuthorizationException
     * @throws ModelNotFoundException
     */
    private function resolveFincaId(array $filters, User $user): int
    {
        $fincaId = $filters['finca_id'] ?? $filters['id_finca'] ?? null;
        if (!$fincaId) {
            $fincasPermitidas = $user->getAllowedFincasIds();
            $fincaId = !empty($fincasPermitidas) ? $fincasPermitidas[0] : null;
        }

        if (!$fincaId) {
            throw new ModelNotFoundException('ID de finca no proporcionado.');
        }

        if ($user->cannot('read', [self::class, (int) $fincaId])) {
            throw new AuthorizationException('No tiene permisos para consultar reportes de esta finca.');
        }

        return (int) $fincaId;
    }
}
