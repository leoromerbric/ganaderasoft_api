<?php

namespace App\Services\Animal;

use App\Models\TipoAnimal;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;

class TipoAnimalService
{
    /**
     * Obtiene el listado de tipos de animal.
     *
     * @param array $filters Filtros.
     * @return LengthAwarePaginator
     */
    public function listTipos(array $filters)
    {
        $query = TipoAnimal::query();

        if (!empty($filters['search'])) {
            $query->byName($filters['search']);
        }

        if (!empty($filters['nopaginate'])) {
            return $query->get();
        }

        return $query->paginate(15);
    }

    /**
     * Registra un nuevo tipo de animal (solo administradores).
     *
     * @param array $data Datos.
     * @param mixed $user Usuario.
     * @return TipoAnimal
     * @throws AuthorizationException
     */
    public function createTipo(array $data, $user): TipoAnimal
    {
        if (!$user->isAdmin()) {
            throw new AuthorizationException('No tiene permisos para crear tipos de animal.');
        }

        return TipoAnimal::create([
            'nombre' => $data['nombre']
        ]);
    }

    /**
     * Obtiene un tipo de animal por su ID.
     *
     * @param int $id ID.
     * @return TipoAnimal
     * @throws ModelNotFoundException
     */
    public function getTipoById(int $id): TipoAnimal
    {
        return TipoAnimal::findOrFail($id);
    }

    /**
     * Actualiza un tipo de animal (solo administradores).
     *
     * @param int $id ID.
     * @param array $data Datos.
     * @param mixed $user Usuario.
     * @return TipoAnimal
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function updateTipo(int $id, array $data, $user): TipoAnimal
    {
        if (!$user->isAdmin()) {
            throw new AuthorizationException('No tiene permisos para actualizar tipos de animal.');
        }

        $tipoAnimal = TipoAnimal::findOrFail($id);

        $payload = [];
        if (array_key_exists('nombre', $data)) $payload['nombre'] = $data['nombre'];

        $tipoAnimal->update($payload);

        return $tipoAnimal;
    }

    /**
     * Elimina un tipo de animal (solo administradores).
     *
     * @param int $id ID.
     * @param mixed $user Usuario.
     * @return bool
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function deleteTipo(int $id, $user): bool
    {
        if (!$user->isAdmin()) {
            throw new AuthorizationException('No tiene permisos para eliminar tipos de animal.');
        }

        $tipoAnimal = TipoAnimal::findOrFail($id);

        return $tipoAnimal->delete();
    }
}
