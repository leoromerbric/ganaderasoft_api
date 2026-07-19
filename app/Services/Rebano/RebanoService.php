<?php

namespace App\Services\Rebano;

use App\Models\Finca;
use App\Models\Rebano;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RebanoService
{
    /**
     * List all rebanos, paginated.
     * Admin sees all, propietario sees only their own.
     */
    public function listRebanos(array $filters, User $user)
    {
        $query = Rebano::with(['finca.propietario', 'animales'])->active();

        if (!$user->isAdmin()) {
            $propietario = $user->propietario;
            if (!$propietario) {
                throw new AuthorizationException('Usuario no es propietario');
            }

            $query->whereHas('finca', function ($q) use ($propietario) {
                $q->where('propietario_id', $propietario->id);
            });
        }

        return $query->paginate(15);
    }

    /**
     * Store a new rebano.
     */
    public function storeRebano(array $data, User $user)
    {
        if (!$user->isAdmin()) {
            $propietario = $user->propietario;
            if (!$propietario) {
                throw new AuthorizationException('Usuario no es propietario');
            }

            $finca = Finca::find($data['finca_id']);
            if (!$finca || $finca->propietario_id != $propietario->id) {
                throw new AuthorizationException('No tiene permisos para crear rebaño en esta finca');
            }
        }

        $rebano = Rebano::create([
            'finca_id' => $data['finca_id'],
            'nombre' => $data['nombre'],
            'archivado' => false,
        ]);

        $rebano->load(['finca.propietario', 'animales']);

        return $rebano;
    }

    /**
     * Get a specific rebano.
     */
    public function getRebano(int $id, User $user)
    {
        $rebano = Rebano::with(['finca.propietario', 'animales'])->find($id);

        if (!$rebano) {
            throw new NotFoundHttpException('Rebaño no encontrado');
        }

        if (!$user->isAdmin()) {
            $propietario = $user->propietario;
            if (!$propietario || $rebano->finca->propietario_id != $propietario->id) {
                throw new AuthorizationException('No tiene permisos para ver este rebaño');
            }
        }

        return $rebano;
    }

    /**
     * Update a rebano.
     */
    public function updateRebano(int $id, array $data, User $user)
    {
        $rebano = $this->getRebano($id, $user);

        if (!$user->isAdmin() && isset($data['finca_id'])) {
            $propietario = $user->propietario;
            $newFinca = Finca::find($data['finca_id']);
            if (!$newFinca || $newFinca->propietario_id != $propietario->id) {
                throw new AuthorizationException('No tiene permisos para mover el rebaño a esa finca');
            }
        }

        $updateData = [];
        if (isset($data['nombre'])) {
            $updateData['nombre'] = $data['nombre'];
        }
        if (isset($data['finca_id'])) {
            $updateData['finca_id'] = $data['finca_id'];
        }

        $rebano->update($updateData);
        $rebano->load(['finca.propietario', 'animales']);

        return $rebano;
    }

    /**
     * Archive (soft delete) a rebano.
     */
    public function archiveRebano(int $id, User $user)
    {
        $rebano = $this->getRebano($id, $user);

        if ($rebano->animales()->count() > 0) {
            throw new ConflictHttpException('No se puede eliminar el rebaño, tiene animales asociados');
        }

        $rebano->update(['archivado' => true]);

        return true;
    }
}
