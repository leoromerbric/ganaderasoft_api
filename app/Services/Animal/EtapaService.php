<?php

namespace App\Services\Animal;

use App\Models\Etapa;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class EtapaService
{
    /**
     * Obtiene la lista de etapas aplicando filtros.
     *
     * @param array $filters Filtros a aplicar.
     * @return LengthAwarePaginator
     */
    public function listEtapas(array $filters)
    {
        $query = Etapa::with(['tipoAnimal']);

        if (!empty($filters['tipo_animal_id'])) {
            $query->where('tipo_animal_id', $filters['tipo_animal_id']);
        }

        if (!empty($filters['sexo'])) {
            $query->where('sexo', $filters['sexo']);
        }

        if (!empty($filters['nombre'])) {
            $query->where('nombre', 'like', '%' . $filters['nombre'] . '%');
        }

        if (!empty($filters['nopaginate'])) {
            return $query->get();
        }

        return $query->paginate(15);
    }

    /**
     * Registra una nueva etapa (solo para administradores).
     *
     * @param array $data Datos de la etapa.
     * @param mixed $user Usuario que realiza la acción.
     * @return Etapa
     * @throws AuthorizationException
     */
    public function createEtapa(array $data, $user): Etapa
    {
        if (!$user->isAdmin()) {
            throw new AuthorizationException('No tiene permisos para crear etapas.');
        }

        return Etapa::create([
            'nombre'         => $data['nombre'],
            'edad_ini'       => $data['edad_ini'],
            'edad_fin'       => $data['edad_fin'] ?? null,
            'tipo_animal_id' => $data['tipo_animal_id'],
            'sexo'           => $data['sexo'],
        ]);
    }

    /**
     * Obtiene una etapa por su ID.
     *
     * @param int $id ID de la etapa.
     * @return Etapa
     * @throws ModelNotFoundException
     */
    public function getEtapaById(int $id): Etapa
    {
        return Etapa::with(['tipoAnimal', 'etapaAnimales.animal'])->findOrFail($id);
    }

    /**
     * Actualiza una etapa (solo para administradores).
     *
     * @param int $id ID de la etapa.
     * @param array $data Datos a actualizar.
     * @param mixed $user Usuario que realiza la acción.
     * @return Etapa
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function updateEtapa(int $id, array $data, $user): Etapa
    {
        if (!$user->isAdmin()) {
            throw new AuthorizationException('No tiene permisos para actualizar etapas.');
        }

        $etapa = Etapa::findOrFail($id);

        $payload = [];
        if (array_key_exists('nombre', $data)) $payload['nombre'] = $data['nombre'];
        if (array_key_exists('edad_ini', $data)) $payload['edad_ini'] = $data['edad_ini'];
        if (array_key_exists('edad_fin', $data)) $payload['edad_fin'] = $data['edad_fin'];
        if (array_key_exists('tipo_animal_id', $data)) $payload['tipo_animal_id'] = $data['tipo_animal_id'];
        if (array_key_exists('sexo', $data)) $payload['sexo'] = $data['sexo'];

        $etapa->update($payload);

        return $etapa;
    }

    /**
     * Elimina una etapa (solo para administradores).
     *
     * @param int $id ID de la etapa.
     * @param mixed $user Usuario que realiza la acción.
     * @return bool
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     * @throws ConflictHttpException
     */
    public function deleteEtapa(int $id, $user): bool
    {
        if (!$user->isAdmin()) {
            throw new AuthorizationException('No tiene permisos para eliminar etapas.');
        }

        $etapa = Etapa::findOrFail($id);

        if ($etapa->etapaAnimales()->count() > 0) {
            throw new ConflictHttpException('No se puede eliminar la etapa porque tiene registros de etapa animal asociados.');
        }

        return $etapa->delete();
    }
}
