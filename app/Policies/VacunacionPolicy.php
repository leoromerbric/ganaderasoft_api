<?php

namespace App\Policies;

use App\Models\Animal;
use App\Models\User;
use App\Models\Vacunacion;

class VacunacionPolicy extends BasePolicy
{
    /**
     * Determina si el usuario puede listar vacunaciones.
     */
    public function readAny(User $user): bool
    {
        return $user->hasPermissionTo('vacunacion.read');
    }

    /**
     * Determina si el usuario puede ver una vacunación específica de un animal.
     */
    public function read(User $user, Vacunacion $vacunacion): bool
    {
        if (!$user->hasPermissionTo('vacunacion.read')) {
            return false;
        }

        $fincaId = optional(optional($vacunacion->animal)->rebano)->finca_id;
        return $this->checkFincaAccess($user, $fincaId);
    }

    /**
     * Determina si el usuario puede crear registros de vacunación.
     */
    public function create(User $user, ?int $animalId = null): bool
    {
        if (!$user->hasPermissionTo('vacunacion.create')) {
            return false;
        }

        if ($animalId) {
            $animal = Animal::with('rebano')->find($animalId);
            $fincaId = optional(optional($animal)->rebano)->finca_id;
            return $this->checkFincaAccess($user, $fincaId);
        }

        return true;
    }

    /**
     * Determina si el usuario puede actualizar una vacunación.
     */
    public function update(User $user, Vacunacion $vacunacion): bool
    {
        if (!$user->hasPermissionTo('vacunacion.update')) {
            return false;
        }

        $fincaId = optional(optional($vacunacion->animal)->rebano)->finca_id;
        return $this->checkFincaAccess($user, $fincaId);
    }

    /**
     * Determina si el usuario puede eliminar una vacunación.
     */
    public function delete(User $user, Vacunacion $vacunacion): bool
    {
        if (!$user->hasPermissionTo('vacunacion.delete')) {
            return false;
        }

        $fincaId = optional(optional($vacunacion->animal)->rebano)->finca_id;
        return $this->checkFincaAccess($user, $fincaId);
    }
}
