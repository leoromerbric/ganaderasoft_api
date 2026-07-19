<?php

namespace App\Services\Terreno;

use App\Models\Terreno;
use App\Models\Finca;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;

class TerrenoService
{
    /**
     * List all terrenos with pagination.
     *
     * @param array $filters
     * @param User $user
     * @return LengthAwarePaginator
     * @throws AuthorizationException
     */
    public function listTerrenos(array $filters, User $user): LengthAwarePaginator
    {
        $query = Terreno::with('finca');

        if ($user->isAdmin()) {
            $this->applyFilters($query, $filters);
            return $query->paginate(15);
        }

        $propietario = $user->propietario;
        if (!$propietario) {
            throw new AuthorizationException('Sin permisos para listar terrenos.');
        }

        $query->whereHas('finca', function ($q) use ($propietario) {
            $q->where('propietario_id', $propietario->id);
        });

        $this->applyFilters($query, $filters);

        return $query->paginate(15);
    }

    /**
     * Store a newly created terreno.
     *
     * @param array $data
     * @param User $user
     * @return Terreno
     * @throws AuthorizationException
     * @throws ModelNotFoundException
     */
    public function storeTerreno(array $data, User $user): Terreno
    {
        $fincaId = (int) $data['finca_id'];
        $finca = Finca::findOrFail($fincaId);

        if (!$user->isAdmin()) {
            $propietario = $user->propietario;
            if (!$propietario || $finca->propietario_id !== $propietario->id) {
                throw new AuthorizationException('No tiene permisos para crear terreno en esta finca.');
            }
        }

        $terreno = Terreno::create($data);

        return $terreno->load('finca');
    }

    /**
     * Get a specific terreno.
     *
     * @param int $id
     * @param User $user
     * @return Terreno
     * @throws AuthorizationException
     * @throws ModelNotFoundException
     */
    public function getTerreno(int $id, User $user): Terreno
    {
        $terreno = Terreno::with('finca')->findOrFail($id);

        if (!$user->isAdmin()) {
            $propietario = $user->propietario;
            if (!$propietario || $terreno->finca->propietario_id !== $propietario->id) {
                throw new AuthorizationException('No tiene permisos para ver este terreno.');
            }
        }

        return $terreno;
    }

    /**
     * Update a terreno.
     *
     * @param int $id
     * @param array $data
     * @param User $user
     * @return Terreno
     * @throws AuthorizationException
     * @throws ModelNotFoundException
     */
    public function updateTerreno(int $id, array $data, User $user): Terreno
    {
        $terreno = Terreno::with('finca')->findOrFail($id);

        if (!$user->isAdmin()) {
            $propietario = $user->propietario;
            if (!$propietario || $terreno->finca->propietario_id !== $propietario->id) {
                throw new AuthorizationException('No tiene permisos para actualizar este terreno.');
            }

            if (isset($data['finca_id']) && (int) $data['finca_id'] !== $terreno->finca_id) {
                $nuevaFinca = Finca::findOrFail((int) $data['finca_id']);
                if ($nuevaFinca->propietario_id !== $propietario->id) {
                    throw new AuthorizationException('No tiene permisos para mover el terreno a esta finca.');
                }
            }
        } else {
            if (isset($data['finca_id']) && (int) $data['finca_id'] !== $terreno->finca_id) {
                Finca::findOrFail((int) $data['finca_id']);
            }
        }

        $terreno->update($data);

        return $terreno->fresh(['finca']);
    }

    /**
     * Delete a terreno (physical delete).
     *
     * @param int $id
     * @param User $user
     * @return bool
     * @throws AuthorizationException
     * @throws ModelNotFoundException
     */
    public function deleteTerreno(int $id, User $user): bool
    {
        $terreno = Terreno::with('finca')->findOrFail($id);

        if (!$user->isAdmin()) {
            $propietario = $user->propietario;
            if (!$propietario || $terreno->finca->propietario_id !== $propietario->id) {
                throw new AuthorizationException('No tiene permisos para eliminar este terreno.');
            }
        }

        return $terreno->delete();
    }

    /**
     * Apply optional filters to the query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return void
     */
    private function applyFilters($query, array $filters): void
    {
        $fincaId = $filters['finca_id'] ?? $filters['id_finca'] ?? $filters['id_Finca'] ?? null;
        if ($fincaId !== null) {
            $query->forFinca($fincaId);
        }

        if (isset($filters['relieve'])) {
            $query->byRelieve($filters['relieve']);
        }
    }
}
