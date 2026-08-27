<?php

namespace App\Policies;

use App\Models\Finca;
use App\Models\User;

class ReportesPolicy extends BasePolicy
{
    /**
     * Determina si el usuario puede ver los reportes del sistema.
     */
    public function readAny(User $user): bool
    {
        if (!$user->hasPermissionTo('reportes.read')) {
            return false;
        }

        if (!$user->isAdmin() && empty($user->getAllowedFincasIds())) {
            return false;
        }

        return true;
    }

    /**
     * Determina si el usuario puede ver los reportes de una finca específica.
     */
    public function read(User $user, mixed $finca = null): bool
    {
        if (!$user->hasPermissionTo('reportes.read')) {
            return false;
        }

        if ($finca === null) {
            return true;
        }

        $fincaId = $finca instanceof Finca ? $finca->id : (int) $finca;

        return $this->checkFincaAccess($user, $fincaId);
    }
}
