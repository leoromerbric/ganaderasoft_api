<?php

namespace App\Services\Inventario;

use App\Models\InventarioBufalo;
use App\Models\Finca;
use Illuminate\Foundation\Auth\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;

class InventarioBufaloService
{
    /**
     * @throws AuthorizationException
     */
    public function listInventarioBufalo(array $filters, User $user)
    {
        $query = InventarioBufalo::with(['finca.propietario'])->recent();

        $fincaId = Arr::get($filters, 'finca_id') ?? Arr::get($filters, 'id_finca');
        if ($fincaId) {
            $query->forFinca($fincaId);
        }

        if ($user->isAdmin()) {
            return $query->paginate(15);
        }

        $propietario = $user->propietario;
        if (!$propietario) {
            throw new AuthorizationException('Usuario no es administrador ni propietario');
        }

        $query->whereHas('finca', function ($q) use ($propietario) {
            $q->where('propietario_id', $propietario->id);
        });

        return $query->paginate(15);
    }

    /**
     * @throws AuthorizationException
     */
    public function storeInventarioBufalo(array $data, User $user)
    {
        $fincaId = $data['finca_id'];

        $this->authorizeFincaAccess($fincaId, $user);

        $data['num_becerro'] = $data['num_becerro'] ?? 0;
        $data['num_anojo'] = $data['num_anojo'] ?? 0;
        $data['num_bubilla'] = $data['num_bubilla'] ?? 0;
        $data['num_bufalo'] = $data['num_bufalo'] ?? 0;

        $inventario = InventarioBufalo::create($data);
        $inventario->load(['finca.propietario']);

        return $inventario;
    }

    /**
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function getInventarioBufalo(int $id, User $user)
    {
        $inventario = InventarioBufalo::with(['finca.propietario'])->findOrFail($id);

        $this->authorizeFincaAccess($inventario->finca_id, $user);

        return $inventario;
    }

    /**
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function updateInventarioBufalo(int $id, array $data, User $user)
    {
        $inventario = $this->getInventarioBufalo($id, $user);

        if (isset($data['finca_id']) && $data['finca_id'] != $inventario->finca_id) {
            $this->authorizeFincaAccess($data['finca_id'], $user);
        }

        $inventario->update($data);

        return $inventario;
    }

    /**
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function deleteInventarioBufalo(int $id, User $user)
    {
        $inventario = $this->getInventarioBufalo($id, $user);
        $inventario->delete();
        return true;
    }

    /**
     * @throws AuthorizationException
     */
    protected function authorizeFincaAccess(int $fincaId, User $user): void
    {
        if ($user->isAdmin()) {
            return;
        }

        $propietario = $user->propietario;
        if (!$propietario) {
            throw new AuthorizationException('Usuario no es administrador ni propietario');
        }

        $finca = Finca::find($fincaId);
        if (!$finca) {
            throw new AuthorizationException('Finca no encontrada o sin acceso');
        }

        if ($finca->propietario_id != $propietario->id) {
            throw new AuthorizationException('No tiene permisos sobre esta finca');
        }
    }
}
