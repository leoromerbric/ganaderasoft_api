<?php

namespace App\Services\Inventario;

use App\Models\InventarioGeneral;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;

class InventarioGeneralService
{
    /**
     * Verifica si el usuario tiene acceso a una finca específica.
     *
     * @param User $user
     * @param int $fincaId
     * @return bool
     */
    protected function userHasAccessToFinca(User $user, int $fincaId): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->fincas()->where('fincas.id', $fincaId)->exists();
    }

    /**
     * Obtener listado de Inventario General con filtros y paginación.
     *
     * @param array $filters
     * @param User $user
     * @return \Illuminate\Pagination\LengthAwarePaginator
     * @throws AuthorizationException
     */
    public function listInventarioGeneral(array $filters, User $user)
    {
        $query = InventarioGeneral::with('finca');

        if (!$user->isAdmin()) {
            $userFincaIds = $user->fincas()->pluck('fincas.id')->toArray();
            $query->whereIn('finca_id', $userFincaIds);
        }

        $fincaId = $filters['finca_id'] ?? ($filters['id_finca'] ?? null);
        if ($fincaId) {
            if (!$this->userHasAccessToFinca($user, $fincaId)) {
                throw new AuthorizationException('No tiene permisos para acceder a los inventarios de esta finca.');
            }
            $query->where('finca_id', $fincaId);
        }

        if (!empty($filters['fecha_inicio'])) {
            $query->where('fecha_inventario', '>=', $filters['fecha_inicio']);
        }
        
        if (!empty($filters['fecha_fin'])) {
            $query->where('fecha_inventario', '<=', $filters['fecha_fin']);
        }

        return $query->paginate(15);
    }

    /**
     * Crear un nuevo registro de Inventario General.
     *
     * @param array $data
     * @param User $user
     * @return InventarioGeneral
     * @throws AuthorizationException
     */
    public function storeInventarioGeneral(array $data, User $user)
    {
        if (isset($data['finca_id']) && !$this->userHasAccessToFinca($user, $data['finca_id'])) {
            throw new AuthorizationException('No tiene permisos para crear inventarios en esta finca.');
        }

        return InventarioGeneral::create($data);
    }

    /**
     * Obtener un registro específico.
     *
     * @param int $id
     * @param User $user
     * @return InventarioGeneral
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function getInventarioGeneral(int $id, User $user)
    {
        $inventario = InventarioGeneral::with('finca')->findOrFail($id);

        if (!$this->userHasAccessToFinca($user, $inventario->finca_id)) {
            throw new AuthorizationException('No tiene permisos para ver este inventario.');
        }

        return $inventario;
    }

    /**
     * Actualizar un registro existente.
     *
     * @param int $id
     * @param array $data
     * @param User $user
     * @return InventarioGeneral
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function updateInventarioGeneral(int $id, array $data, User $user)
    {
        $inventario = InventarioGeneral::findOrFail($id);

        if (!$this->userHasAccessToFinca($user, $inventario->finca_id)) {
            throw new AuthorizationException('No tiene permisos para actualizar este inventario.');
        }

        if (isset($data['finca_id']) && $data['finca_id'] != $inventario->finca_id) {
            if (!$this->userHasAccessToFinca($user, $data['finca_id'])) {
                throw new AuthorizationException('No tiene permisos para asignar el inventario a la nueva finca.');
            }
        }

        $inventario->update($data);

        return $inventario;
    }

    /**
     * Eliminar físicamente un registro.
     *
     * @param int $id
     * @param User $user
     * @return bool|null
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function deleteInventarioGeneral(int $id, User $user)
    {
        $inventario = InventarioGeneral::findOrFail($id);

        if (!$this->userHasAccessToFinca($user, $inventario->finca_id)) {
            throw new AuthorizationException('No tiene permisos para eliminar este inventario.');
        }

        return $inventario->delete();
    }
}
