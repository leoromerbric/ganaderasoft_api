<?php

namespace App\Services\Sanidad;

use App\Models\EstadoSalud;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class EstadoSaludService
{
    /**
     * Obtiene la lista de estados de salud con paginación.
     *
     * @param array $filters Filtros.
     * @return LengthAwarePaginator
     */
    public function listEstados(array $filters)
    {
        $query = EstadoSalud::query();

        if (!empty($filters['search'])) {
            $query->byName($filters['search']);
        }

        if (!empty($filters['nopaginate'])) {
            return $query->get();
        }

        return $query->paginate(15);
    }

    /**
     * Registra un nuevo estado de salud (solo administradores).
     *
     * @param array $data Datos.
     * @param mixed $user Usuario.
     * @return EstadoSalud
     * @throws AuthorizationException
     */
    public function createEstado(array $data, $user): EstadoSalud
    {
        if (!$user->isAdmin()) {
            throw new AuthorizationException('No tiene permisos para crear estados de salud.');
        }

        return EstadoSalud::create([
            'nombre' => $data['nombre']
        ]);
    }

    /**
     * Obtiene un estado de salud por su ID.
     *
     * @param int $id ID del estado.
     * @return EstadoSalud
     * @throws ModelNotFoundException
     */
    public function getEstadoById(int $id): EstadoSalud
    {
        return EstadoSalud::with(['estadosAnimal.animal'])->findOrFail($id);
    }

    /**
     * Actualiza los datos de un estado de salud (solo administradores).
     *
     * @param int $id ID.
     * @param array $data Datos a actualizar.
     * @param mixed $user Usuario.
     * @return EstadoSalud
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function updateEstado(int $id, array $data, $user): EstadoSalud
    {
        if (!$user->isAdmin()) {
            throw new AuthorizationException('No tiene permisos para actualizar estados de salud.');
        }

        $estadoSalud = EstadoSalud::findOrFail($id);

        $payload = [];
        if (array_key_exists('nombre', $data)) $payload['nombre'] = $data['nombre'];

        $estadoSalud->update($payload);

        return $estadoSalud;
    }

    /**
     * Elimina un estado de salud (solo administradores).
     *
     * @param int $id ID.
     * @param mixed $user Usuario.
     * @return bool
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     * @throws ConflictHttpException
     */
    public function deleteEstado(int $id, $user): bool
    {
        if (!$user->isAdmin()) {
            throw new AuthorizationException('No tiene permisos para eliminar estados de salud.');
        }

        $estadoSalud = EstadoSalud::findOrFail($id);

        if ($estadoSalud->estadosAnimal()->count() > 0) {
            throw new ConflictHttpException('No se puede eliminar el estado de salud porque está siendo utilizado.');
        }

        return $estadoSalud->delete();
    }
}
