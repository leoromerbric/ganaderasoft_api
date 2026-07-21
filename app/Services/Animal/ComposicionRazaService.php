<?php

namespace App\Services\Animal;

use App\Models\ComposicionRaza;
use App\Models\Finca;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class ComposicionRazaService
{
    /**
     * Obtiene el listado de composiciones de raza.
     *
     * @param array $filters Filtros.
     * @return LengthAwarePaginator
     */
    public function listComposiciones(array $filters)
    {
        $query = ComposicionRaza::with(['finca', 'tipoAnimal']);

        if (!empty($filters['nombre'])) {
            $query->byName($filters['nombre']);
        }

        if (!empty($filters['nopaginate'])) {
            return $query->get();
        }

        return $query->paginate(15);
    }

    /**
     * Registra una nueva composición de raza.
     *
     * @param array $data Datos del registro.
     * @param mixed $user Usuario que realiza la acción.
     * @return ComposicionRaza
     * @throws AuthorizationException
     */
    public function createComposicion(array $data, $user): ComposicionRaza
    {
        // Verificar permisos si se especifica una finca
        if (!empty($data['finca_id']) && !$user->isAdmin()) {
            $propietario = $user->propietario;
            if (!$propietario) {
                throw new AuthorizationException('Usuario no tiene propietario asociado.');
            }

            $finca = Finca::find($data['finca_id']);
            if (!$finca || $finca->propietario_id != $propietario->id) {
                throw new AuthorizationException('No tiene permisos para asociar esta composición con la finca especificada.');
            }
        }

        return ComposicionRaza::create([
            'nombre'                  => $data['nombre'],
            'siglas'                  => $data['siglas'] ?? null,
            'pelaje'                  => $data['pelaje'] ?? null,
            'proposito'               => $data['proposito'] ?? null,
            'tipo_raza'               => $data['tipo_raza'] ?? null,
            'origen'                  => $data['origen'] ?? null,
            'caracteristica_especial' => $data['caracteristica_especial'] ?? null,
            'proporcion_raza'         => $data['proporcion_raza'] ?? null,
            'finca_id'                => $data['finca_id'] ?? null,
            'tipo_animal_id'          => $data['tipo_animal_id'] ?? null,
        ]);
    }

    /**
     * Obtiene una composición de raza por su ID.
     *
     * @param int $id ID de la composición.
     * @param mixed $user Usuario.
     * @return ComposicionRaza
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function getComposicionById(int $id, $user): ComposicionRaza
    {
        $composicionRaza = ComposicionRaza::with(['finca', 'tipoAnimal', 'animales'])->findOrFail($id);

        if (!$user->isAdmin() && $composicionRaza->finca) {
            $propietario = $user->propietario;
            if (!$propietario || $composicionRaza->finca->propietario_id != $propietario->id) {
                throw new AuthorizationException('No tiene permisos para ver esta composición de raza.');
            }
        }

        return $composicionRaza;
    }

    /**
     * Actualiza una composición de raza.
     *
     * @param int $id ID del registro.
     * @param array $data Datos a actualizar.
     * @param mixed $user Usuario.
     * @return ComposicionRaza
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function updateComposicion(int $id, array $data, $user): ComposicionRaza
    {
        $composicionRaza = ComposicionRaza::findOrFail($id);

        if (!$user->isAdmin() && $composicionRaza->finca) {
            $propietario = $user->propietario;
            if (!$propietario || $composicionRaza->finca->propietario_id != $propietario->id) {
                throw new AuthorizationException('No tiene permisos para actualizar esta composición de raza.');
            }
        }

        $payload = [];
        if (array_key_exists('nombre', $data)) $payload['nombre'] = $data['nombre'];
        if (array_key_exists('siglas', $data)) $payload['siglas'] = $data['siglas'];
        if (array_key_exists('pelaje', $data)) $payload['pelaje'] = $data['pelaje'];
        if (array_key_exists('proposito', $data)) $payload['proposito'] = $data['proposito'];
        if (array_key_exists('tipo_raza', $data)) $payload['tipo_raza'] = $data['tipo_raza'];
        if (array_key_exists('origen', $data)) $payload['origen'] = $data['origen'];
        if (array_key_exists('caracteristica_especial', $data)) $payload['caracteristica_especial'] = $data['caracteristica_especial'];
        if (array_key_exists('proporcion_raza', $data)) $payload['proporcion_raza'] = $data['proporcion_raza'];
        if (array_key_exists('finca_id', $data)) $payload['finca_id'] = $data['finca_id'];
        if (array_key_exists('tipo_animal_id', $data)) $payload['tipo_animal_id'] = $data['tipo_animal_id'];

        $composicionRaza->update($payload);

        return $composicionRaza;
    }

    /**
     * Elimina una composición de raza.
     *
     * @param int $id ID de la composición.
     * @param mixed $user Usuario.
     * @return bool
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     * @throws ConflictHttpException
     */
    public function deleteComposicion(int $id, $user): bool
    {
        $composicionRaza = ComposicionRaza::findOrFail($id);

        if (!$user->isAdmin() && $composicionRaza->finca) {
            $propietario = $user->propietario;
            if (!$propietario || $composicionRaza->finca->propietario_id != $propietario->id) {
                throw new AuthorizationException('No tiene permisos para eliminar esta composición de raza.');
            }
        }

        if ($composicionRaza->animales()->count() > 0) {
            throw new ConflictHttpException('No se puede eliminar la composición de raza porque tiene animales asociados.');
        }

        return $composicionRaza->delete();
    }
}
