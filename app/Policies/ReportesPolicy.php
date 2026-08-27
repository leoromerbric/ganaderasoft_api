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
        return $user->hasPermissionTo('reportes.read');
    }

    /**
     * Determina si el usuario puede ver los reportes.
     */
    public function read(User $user, mixed $target = null): bool
    {
        return $user->hasPermissionTo('reportes.read');
    }
}
