<?php

namespace App\Services\Rebano;

use App\Models\Finca;
use App\Models\Rebano;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Services\BaseService;

class RebanoService extends BaseService
{
    /**
     * Listar todos los rebaños, paginado.
     * Admin ve todos, propietario ve solo los suyos.
     */
    public function listRebanos(array $filters, User $user)
    {
        if ($user->cannot('readAny', Rebano::class)) {
            throw new AuthorizationException('Sin permisos para listar rebaños.');
        }

        $query = Rebano::with(['finca.propietario', 'animales'])->withCount('animales')->active();

        if (!empty($filters['finca_id'])) {
            $query->where('finca_id', $filters['finca_id']);
        }

        $this->applyFincaFilter($query, $user, null);

        if (!empty($filters['nopaginate'])) {
            return $query->get();
        }

        return $query->paginate(15);
    }

    /**
     * Crear un nuevo rebaño.
     */
    public function storeRebano(array $data, User $user)
    {
        $fincaId = (int) $data['finca_id'];
        
        if ($user->cannot('create', [Rebano::class, $fincaId])) {
            throw new AuthorizationException('No tiene permisos para crear rebaño en esta finca');
        }

        $rebano = Rebano::create([
            'finca_id' => $data['finca_id'],
            'nombre' => $data['nombre'],
            'archivado' => false,
        ]);

        $rebano->load(['finca.propietario', 'animales'])->loadCount('animales');

        return $rebano;
    }

    /**
     * Obtener un rebaño específico.
     */
    public function getRebano(int $id, User $user)
    {
        $rebano = Rebano::with(['finca.propietario', 'animales'])->withCount('animales')->find($id);

        if (!$rebano) {
            throw new NotFoundHttpException('Rebaño no encontrado');
        }

        if ($user->cannot('read', $rebano)) {
            throw new AuthorizationException('No tiene permisos para ver este rebaño');
        }

        return $rebano;
    }

    /**
     * Actualizar los datos de un rebaño.
     */
    public function updateRebano(int $id, array $data, User $user)
    {
        $rebano = $this->getRebano($id, $user);

        if ($user->cannot('update', $rebano)) {
            throw new AuthorizationException('No tiene permisos para actualizar este rebaño');
        }

        if (isset($data['finca_id']) && (int) $data['finca_id'] !== $rebano->finca_id) {
            if ($user->cannot('create', [Rebano::class, (int) $data['finca_id']])) {
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
     * Archivar (borrado lógico) un rebaño.
     */
    public function archiveRebano(int $id, User $user)
    {
        $rebano = $this->getRebano($id, $user);
        
        if ($user->cannot('delete', $rebano)) {
            throw new AuthorizationException('No tiene permisos para archivar este rebaño');
        }

        if ($rebano->animales()->count() > 0) {
            throw new ConflictHttpException('No se puede eliminar el rebaño, tiene animales asociados');
        }

        $rebano->update(['archivado' => true]);

        return true;
    }
}
