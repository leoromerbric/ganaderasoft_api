<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

abstract class BasePolicy
{
    use HandlesAuthorization;

    /**
     * Interceptar comprobaciones para conceder acceso total a Administradores.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null; // Deja que los demás métodos hagan su trabajo si no es admin
    }

    /**
     * Valida si el usuario tiene acceso a la finca especificada.
     * 
     * @param User $user
     * @param int|null $fincaId
     * @return bool
     */
    protected function checkFincaAccess(User $user, ?int $fincaId): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (!$fincaId) {
            return false;
        }

        return in_array($fincaId, $user->getAllowedFincasIds());
    }
}
