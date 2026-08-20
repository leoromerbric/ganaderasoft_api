<?php

namespace App\Services\Sanidad;

use App\Models\Animal;
use App\Models\Vacunacion;
use App\Services\BaseService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VacunacionService extends BaseService
{
    /**
     * Obtiene una lista paginada de vacunaciones con filtros y aislamiento multi-finca.
     */
    public function getPaginatedVacunaciones(array $filters, $user, int $perPage = 15)
    {
        if ($user->cannot('readAny', Vacunacion::class)) {
            throw new AuthorizationException('No tienes permisos para listar vacunaciones.');
        }

        $query = Vacunacion::with(['animal.rebano.finca', 'vacuna', 'aplicador']);

        if (!empty($filters['animal_id'])) {
            $query->forAnimal((int) $filters['animal_id']);
        }

        if (!empty($filters['vacuna_id'])) {
            $query->forVacuna((int) $filters['vacuna_id']);
        }

        if (!empty($filters['rebano_id'])) {
            $query->forRebano((int) $filters['rebano_id']);
        }

        if (!empty($filters['finca_id'])) {
            $query->forFinca((int) $filters['finca_id']);
        }

        if (!empty($filters['fecha_inicio']) || !empty($filters['fecha_fin'])) {
            $query->betweenDates($filters['fecha_inicio'] ?? null, $filters['fecha_fin'] ?? null);
        }

        // Filtro automático multi-finca
        $this->applyFincaFilter($query, $user, 'animal.rebano');

        if (isset($filters['nopaginate']) && filter_var($filters['nopaginate'], FILTER_VALIDATE_BOOLEAN)) {
            return $query->orderByDesc('fecha')->orderByDesc('id')->get();
        }

        return $query->orderByDesc('fecha')->orderByDesc('id')->paginate($perPage);
    }

    /**
     * Obtiene animales elegibles para vacunación según rebaño, sexo y etapa.
     */
    public function getAnimalesElegibles(array $filters, $user)
    {
        if ($user->cannot('readAny', Vacunacion::class)) {
            throw new AuthorizationException('No tienes permisos para consultar animales elegibles.');
        }

        $query = Animal::query()
            ->where('rebano_id', (int) $filters['rebano_id'])
            ->where('archivado', false);

        if (!empty($filters['sexo'])) {
            $query->where('sexo', $filters['sexo']);
        }

        if (!empty($filters['etapa_id'])) {
            $etapaId = (int) $filters['etapa_id'];
            $query->whereHas('etapaAnimales', function ($q) use ($etapaId) {
                $q->where('etapa_id', $etapaId)
                  ->where(function ($sq) {
                      $sq->whereNull('fecha_fin')
                         ->orWhere('fecha_fin', '>', now()->toDateString());
                  });
            });
        }

        // Filtro multi-finca
        $this->applyFincaFilter($query, $user, 'rebano');

        return $query->orderBy('nombre')
            ->get(['id', 'rebano_id', 'nombre', 'codigo_animal', 'sexo']);
    }

    /**
     * Registra una o múltiples vacunaciones en una transacción.
     */
    public function createVacunacion(array $data, $user)
    {
        if ($user->cannot('create', Vacunacion::class)) {
            throw new AuthorizationException('No tienes permisos para registrar vacunaciones.');
        }

        // Lista de animales
        $animalIds = !empty($data['animal_ids'])
            ? collect($data['animal_ids'])->unique()->all()
            : [(int) ($data['animal_id'] ?? 0)];

        if (empty($animalIds) || $animalIds[0] === 0) {
            throw ValidationException::withMessages([
                'animal_id' => ['Debe indicar al menos un animal a vacunar.']
            ]);
        }

        // Validar acceso multi-finca a todos los animales
        if (!$user->isAdmin()) {
            $fincasPermitidas = $user->getAllowedFincasIds();
            $validCount = Animal::whereIn('id', $animalIds)
                ->whereHas('rebano', fn($q) => $q->whereIn('finca_id', $fincasPermitidas))
                ->count();

            if ($validCount !== count($animalIds)) {
                throw new AuthorizationException('No tienes permiso para vacunar animales de fincas no autorizadas.');
            }
        }

        $vacunaId   = (int) $data['vacuna_id'];
        $personaId  = !empty($data['persona_id']) ? (int) $data['persona_id'] : null;
        $fecha      = $data['fecha'];
        $dosis      = isset($data['dosis']) ? (float) $data['dosis'] : null;
        $costo      = (float) ($data['costo'] ?? 0);
        $lote       = $data['lote'] ?? null;
        $observacion = $data['observacion'] ?? null;

        return DB::transaction(function () use ($animalIds, $vacunaId, $personaId, $fecha, $dosis, $costo, $lote, $observacion) {
            $records = [];
            foreach ($animalIds as $animalId) {
                $vacunacion = Vacunacion::updateOrCreate(
                    [
                        'animal_id' => $animalId,
                        'vacuna_id' => $vacunaId,
                        'fecha'     => $fecha,
                    ],
                    [
                        'persona_id'  => $personaId,
                        'dosis'       => $dosis,
                        'costo'       => $costo,
                        'lote'        => $lote,
                        'observacion' => $observacion,
                    ]
                );
                $records[] = $vacunacion;
            }
            return $records;
        });
    }

    /**
     * Obtiene una vacunación por su ID.
     */
    public function getVacunacionById($id, $user): Vacunacion
    {
        $vacunacion = Vacunacion::with(['animal.rebano.finca', 'vacuna', 'aplicador'])->findOrFail($id);

        if ($user->cannot('read', $vacunacion)) {
            throw new AuthorizationException('No tienes permisos para ver esta vacunación.');
        }

        return $vacunacion;
    }

    /**
     * Actualiza un registro de vacunación existente.
     */
    public function updateVacunacion($id, array $data, $user): Vacunacion
    {
        $vacunacion = Vacunacion::findOrFail($id);

        if ($user->cannot('update', $vacunacion)) {
            throw new AuthorizationException('No tienes permisos para actualizar esta vacunación.');
        }

        $vacunacion->update($data);
        return $vacunacion->load(['animal.rebano.finca', 'vacuna', 'aplicador']);
    }

    /**
     * Elimina un registro de vacunación.
     */
    public function deleteVacunacion($id, $user): bool
    {
        $vacunacion = Vacunacion::findOrFail($id);

        if ($user->cannot('delete', $vacunacion)) {
            throw new AuthorizationException('No tienes permisos para eliminar esta vacunación.');
        }

        return (bool) $vacunacion->delete();
    }
}
