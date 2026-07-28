<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Animal;
use App\Models\Rebano;

class AnimalPolicy extends BasePolicy
{
    /**
     * Determina si el usuario puede ver cualquier animal.
     */
    public function readAny(User $user): bool
    {
        return $user->hasPermissionTo('animal.read');
    }

    /**
     * Determina si el usuario puede ver un animal en específico.
     */
    public function read(User $user, Animal $animal): bool
    {
        if (!$user->hasPermissionTo('animal.read')) {
            return false;
        }

        // El animal pertenece a un Rebaño que a su vez pertenece a una Finca
        return $this->checkFincaAccess($user, $animal->rebano->finca_id);
    }

    /**
     * Determina si el usuario puede crear un animal.
     */
    public function create(User $user, int $rebanoId): bool
    {
        if (!$user->hasPermissionTo('animal.create')) {
            return false;
        }

        $rebano = Rebano::find($rebanoId);
        return $rebano ? $this->checkFincaAccess($user, $rebano->finca_id) : false;
    }

    /**
     * Determina si el usuario puede actualizar el animal.
     */
    public function update(User $user, Animal $animal): bool
    {
        if (!$user->hasPermissionTo('animal.update')) {
            return false;
        }

        return $this->checkFincaAccess($user, $animal->rebano->finca_id);
    }

    /**
     * Determina si el usuario puede eliminar/archivar el animal.
     */
    public function delete(User $user, Animal $animal): bool
    {
        if (!$user->hasPermissionTo('animal.delete')) {
            return false;
        }

        return $this->checkFincaAccess($user, $animal->rebano->finca_id);
    }
}
