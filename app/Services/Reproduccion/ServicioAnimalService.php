<?php

namespace App\Services\Reproduccion;

use App\Models\ServicioAnimal;
use App\Models\Animal;
use App\Models\RegistroCelo;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

use App\Services\BaseService;

class ServicioAnimalService extends BaseService
{
    /**
     * Obtiene una lista paginada de servicios a animales basándose en los filtros y la autorización del usuario.
     */
    public function getPaginatedServicios(array $filters, $user = null, $perPage = 15)
    {
        $user = $user ?? auth()->user();

        if ($user->cannot('readAny', ServicioAnimal::class)) {
            throw new AuthorizationException('Sin permisos para listar servicios.');
        }

        $query = ServicioAnimal::with(['animal', 'semen', 'tecnico', 'registroCelo']);

        if (isset($filters['animal_id'])) {
            $query->forAnimal($filters['animal_id']);
        }
        if (isset($filters['tipo'])) {
            $query->byTipo($filters['tipo']);
        }
        if (isset($filters['fecha_inicio'])) {
            $fechaFin = $filters['fecha_fin'] ?? date('Y-m-d');
            $query->byDateRange($filters['fecha_inicio'], $fechaFin);
        }

        $this->applyFincaFilter($query, $user, 'animal.rebano');

        if (isset($filters['nopaginate'])) {
            return $query->get();
        }

        return $query->paginate($perPage);
    }

    /**
     * Crea un nuevo registro de servicio, resolviendo animal_etapa_id si es necesario.
     */
    public function createServicio(array $data, $user = null)
    {
        $user = $user ?? auth()->user();
        $animalId = $data['animal_id'] ?? null;

        if ($user->cannot('create', [ServicioAnimal::class, $animalId])) {
            throw new AuthorizationException('No tiene permisos para registrar un servicio a este animal.');
        }

        if (isset($data['animal_id'])) {
            $this->validateFemaleAnimal($data['animal_id']);
            if (!empty($data['registro_celo_id'])) {
                $this->validateCeloBelongsToAnimal($data['registro_celo_id'], $data['animal_id']);
            }
        }

        $servicio = ServicioAnimal::create([
            'animal_id'         => $data['animal_id'],
            'semen_toro_id'     => $data['semen_toro_id'] ?? null,
            'personal_finca_id' => $data['personal_finca_id'] ?? null,
            'registro_celo_id'  => $data['registro_celo_id'] ?? null,
            'tipo'              => $data['tipo'] ?? null,
            'fecha'             => $data['fecha'] ?? null,
            'observacion'       => $data['observacion'] ?? null,
        ]);

        return $servicio->load(['animal', 'semen', 'tecnico', 'registroCelo']);
    }

    /**
     * Obtiene un servicio específico por su ID.
     */
    public function getServicioById($id, $user = null)
    {
        $user = $user ?? auth()->user();
        $servicio = ServicioAnimal::with(['animal', 'semen', 'tecnico', 'registroCelo'])->findOrFail($id);

        if ($user->cannot('read', $servicio)) {
            throw new AuthorizationException('No tiene permisos para ver este servicio.');
        }

        return $servicio;
    }

    /**
     * Actualiza un registro de servicio existente.
     */
    public function updateServicio($id, array $data, $user = null)
    {
        $user = $user ?? auth()->user();
        $servicio = ServicioAnimal::findOrFail($id);

        if ($user->cannot('update', $servicio)) {
            throw new AuthorizationException('No tiene permisos para actualizar este servicio.');
        }

        $targetAnimalId = $data['animal_id'] ?? $servicio->animal_id;
        $targetCeloId = array_key_exists('registro_celo_id', $data) ? $data['registro_celo_id'] : $servicio->registro_celo_id;

        if (isset($data['animal_id']) && $data['animal_id'] != $servicio->animal_id) {
            if ($user->cannot('create', [ServicioAnimal::class, (int) $data['animal_id']])) {
                throw new AuthorizationException('No tiene permisos para asignar un servicio a ese nuevo animal.');
            }
            $this->validateFemaleAnimal($targetAnimalId);
        }

        if ($targetCeloId && (isset($data['registro_celo_id']) || isset($data['animal_id']))) {
            $this->validateCeloBelongsToAnimal($targetCeloId, $targetAnimalId);
        }

        $updatePayload = [];
        $fields = [
            'animal_id', 'semen_toro_id', 'personal_finca_id',
            'registro_celo_id', 'tipo', 'fecha', 'observacion'
        ];

        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $updatePayload[$field] = $data[$field];
            }
        }

        $servicio->update($updatePayload);

        return $servicio->load(['animal', 'semen', 'tecnico', 'registroCelo']);
    }

    /**
     * Elimina un registro de servicio existente.
     */
    public function deleteServicio($id, $user = null)
    {
        $user = $user ?? auth()->user();
        $servicio = ServicioAnimal::findOrFail($id);

        if ($user->cannot('delete', $servicio)) {
            throw new AuthorizationException('No tiene permisos para eliminar este servicio.');
        }

        return $servicio->delete();
    }

    /**
     * Valida que el animal sea hembra ('H').
     */
    private function validateFemaleAnimal($animalId): void
    {
        $animal = Animal::findOrFail($animalId);
        $sexo = strtoupper((string) $animal->sexo);

        if (!in_array($sexo, ['H', 'F', 'HEMBRA', 'FEMENINO'], true)) {
            throw ValidationException::withMessages([
                'animal_id' => ['El servicio de reproducción solo puede ser registrado en animales hembras (H).']
            ]);
        }
    }

    /**
     * Valida que el registro de celo provenga del mismo animal.
     */
    private function validateCeloBelongsToAnimal($registroCeloId, $animalId): void
    {
        if (!$registroCeloId || !$animalId) {
            return;
        }

        $celo = RegistroCelo::with('etapaAnimal')->findOrFail($registroCeloId);

        if (!$celo->etapaAnimal || $celo->etapaAnimal->animal_id != $animalId) {
            throw ValidationException::withMessages([
                'registro_celo_id' => ['El evento de celo especificado no pertenece al animal seleccionado. No se puede vincular un celo de un animal diferente.']
            ]);
        }
    }
}
