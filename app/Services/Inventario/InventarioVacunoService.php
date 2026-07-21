<?php

namespace App\Services\Inventario;

use App\Models\InventarioVacuno;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;

class InventarioVacunoService
{
    /**
     * @throws AuthorizationException
     */
    public function listInventarioVacuno(array $filters, User $user): LengthAwarePaginator
    {
        $query = InventarioVacuno::query()->with('finca');

        if (!$user->isAdmin()) {
            $fincaIds = $user->fincas()->pluck('fincas.id')->toArray();
            
            $fincaIdFilter = $filters['finca_id'] ?? $filters['id_finca'] ?? null;
            
            if ($fincaIdFilter) {
                if (!in_array($fincaIdFilter, $fincaIds)) {
                    throw new AuthorizationException('No tienes permiso para ver los inventarios de esta finca.');
                }
                $query->forFinca($fincaIdFilter);
            } else {
                $query->whereIn('finca_id', $fincaIds);
            }
        } else {
            $fincaIdFilter = $filters['finca_id'] ?? $filters['id_finca'] ?? null;
            if ($fincaIdFilter) {
                $query->forFinca($fincaIdFilter);
            }
        }

        if (isset($filters['fecha_inicio'])) {
            $query->byDateRange($filters['fecha_inicio'], $filters['fecha_fin'] ?? null);
        }

        return $query->paginate(15);
    }

    /**
     * @throws AuthorizationException
     */
    public function storeInventarioVacuno(array $data, User $user): InventarioVacuno
    {
        $this->authorizeFinca($data['finca_id'], $user);

        return InventarioVacuno::create($data);
    }

    /**
     * @throws AuthorizationException
     * @throws ModelNotFoundException
     */
    public function getInventarioVacuno(int $id, User $user): InventarioVacuno
    {
        $inventario = InventarioVacuno::with('finca')->findOrFail($id);

        $this->authorizeFinca($inventario->finca_id, $user);

        return $inventario;
    }

    /**
     * @throws AuthorizationException
     * @throws ModelNotFoundException
     */
    public function updateInventarioVacuno(int $id, array $data, User $user): InventarioVacuno
    {
        $inventario = $this->getInventarioVacuno($id, $user);

        if (isset($data['finca_id'])) {
            $this->authorizeFinca($data['finca_id'], $user);
        }

        $inventario->update($data);

        return $inventario;
    }

    /**
     * @throws AuthorizationException
     * @throws ModelNotFoundException
     */
    public function deleteInventarioVacuno(int $id, User $user): bool
    {
        $inventario = $this->getInventarioVacuno($id, $user);

        return $inventario->delete();
    }

    /**
     * @throws AuthorizationException
     */
    protected function authorizeFinca(int $fincaId, User $user): void
    {
        if ($user->isAdmin()) {
            return;
        }

        $isOwner = $user->fincas()->where('fincas.id', $fincaId)->exists();

        if (!$isOwner) {
            throw new AuthorizationException('No tienes permisos sobre esta finca.');
        }
    }
}
