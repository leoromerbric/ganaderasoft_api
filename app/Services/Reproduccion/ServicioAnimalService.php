<?php

namespace App\Services\Reproduccion;

use App\Models\ServicioAnimal;
use App\Models\Animal;
use App\Models\RegistroCelo;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

class ServicioAnimalService
{
    /**
     * Retrieve paginated servicios with applied filters and authorization.
     */
    public function getPaginatedServicios(array $filters, $user, $perPage = 15)
    {
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

        if (isset($filters['nopaginate'])) {
            return $query->get();
        }

        // Control de acceso para propietarios no-administradores
        if (!$user->isAdmin() && $user->isPropietario()) {
            $propietario = $user->propietario;
            if ($propietario) {
                $query->whereHas('animal.rebano.finca', function ($q) use ($propietario) {
                    $q->where('propietario_id', $propietario->id);
                });
            }
        }

        return $query->paginate($perPage);
    }

    /**
     * Create a new ServicioAnimal.
     */
    public function createServicio(array $data)
    {
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
     * Fetch a specific ServicioAnimal by ID with relationships.
     */
    public function getServicioById($id)
    {
        return ServicioAnimal::with(['animal', 'semen', 'tecnico', 'registroCelo'])->findOrFail($id);
    }

    /**
     * Update an existing ServicioAnimal.
     */
    public function updateServicio($id, array $data)
    {
        $servicio = ServicioAnimal::findOrFail($id);

        $targetAnimalId = $data['animal_id'] ?? $servicio->animal_id;
        $targetCeloId = array_key_exists('registro_celo_id', $data) ? $data['registro_celo_id'] : $servicio->registro_celo_id;

        if (isset($data['animal_id']) && $data['animal_id'] != $servicio->animal_id) {
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
     * Delete an existing ServicioAnimal.
     */
    public function deleteServicio($id)
    {
        $servicio = ServicioAnimal::findOrFail($id);
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
