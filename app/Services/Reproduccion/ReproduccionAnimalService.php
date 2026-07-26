<?php

namespace App\Services\Reproduccion;

use App\Models\ReproduccionAnimal;
use App\Models\EtapaAnimal;
use App\Models\Animal;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

class ReproduccionAnimalService
{
    /**
     * Retrieve paginated reproducciones with applied filters and authorization.
     */
    public function getPaginatedReproducciones(array $filters, $user, $perPage = 15)
    {
        $query = ReproduccionAnimal::with(['etapaAnimal.animal', 'etapaAnimal.etapa', 'animal', 'etapa']);

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
                $query->whereHas('etapaAnimal.animal.rebano.finca', function ($q) use ($propietario) {
                    $q->where('propietario_id', $propietario->id);
                });
            }
        }

        return $query->paginate($perPage);
    }

    /**
     * Create a new ReproduccionAnimal resolving animal_etapa_id if needed.
     */
    public function createReproduccion(array $data)
    {
        if (!isset($data['animal_etapa_id']) && isset($data['animal_id']) && isset($data['etapa_id'])) {
            $etapaAnimal = EtapaAnimal::where('animal_id', $data['animal_id'])
                ->where('etapa_id', $data['etapa_id'])
                ->first();

            if ($etapaAnimal) {
                $data['animal_etapa_id'] = $etapaAnimal->id;
            } else {
                throw ValidationException::withMessages([
                    'animal_etapa_id' => ['La combinación de animal y etapa especificada no existe.']
                ]);
            }
        }

        if (isset($data['animal_etapa_id'])) {
            $this->validateFemaleEtapaAnimal($data['animal_etapa_id']);
        } elseif (isset($data['animal_id'])) {
            $this->validateFemaleAnimal($data['animal_id']);
        }

        $repro = ReproduccionAnimal::create([
            'animal_etapa_id'    => $data['animal_etapa_id'],
            'fecha_reproduccion' => $data['fecha_reproduccion'],
            'tipo_reproduccion'  => $data['tipo_reproduccion'] ?? null,
            'observacion'        => $data['observacion'] ?? null,
        ]);

        return $repro->load(['etapaAnimal.animal', 'etapaAnimal.etapa', 'animal', 'etapa']);
    }

    /**
     * Fetch a specific ReproduccionAnimal by ID with relationships.
     */
    public function getReproduccionById($id)
    {
        return ReproduccionAnimal::with(['etapaAnimal.animal', 'etapaAnimal.etapa', 'animal', 'etapa'])->findOrFail($id);
    }

    /**
     * Update an existing ReproduccionAnimal.
     */
    public function updateReproduccion($id, array $data)
    {
        $repro = ReproduccionAnimal::findOrFail($id);

        if (!isset($data['animal_etapa_id']) && isset($data['animal_id']) && isset($data['etapa_id'])) {
            $etapaAnimal = EtapaAnimal::where('animal_id', $data['animal_id'])
                ->where('etapa_id', $data['etapa_id'])
                ->first();

            if ($etapaAnimal) {
                $data['animal_etapa_id'] = $etapaAnimal->id;
            } else {
                throw ValidationException::withMessages([
                    'animal_etapa_id' => ['La combinación de animal y etapa especificada no existe.']
                ]);
            }
        }

        if (isset($data['animal_etapa_id']) && $data['animal_etapa_id'] != $repro->animal_etapa_id) {
            $this->validateFemaleEtapaAnimal($data['animal_etapa_id']);
        } elseif (isset($data['animal_id'])) {
            $this->validateFemaleAnimal($data['animal_id']);
        }

        $updatePayload = [];
        if (array_key_exists('animal_etapa_id', $data)) {
            $updatePayload['animal_etapa_id'] = $data['animal_etapa_id'];
        }
        if (array_key_exists('fecha_reproduccion', $data)) {
            $updatePayload['fecha_reproduccion'] = $data['fecha_reproduccion'];
        }
        if (array_key_exists('tipo_reproduccion', $data)) {
            $updatePayload['tipo_reproduccion'] = $data['tipo_reproduccion'];
        }
        if (array_key_exists('observacion', $data)) {
            $updatePayload['observacion'] = $data['observacion'];
        }

        $repro->update($updatePayload);

        return $repro->load(['etapaAnimal.animal', 'etapaAnimal.etapa', 'animal', 'etapa']);
    }

    /**
     * Delete an existing ReproduccionAnimal.
     */
    public function deleteReproduccion($id)
    {
        $repro = ReproduccionAnimal::findOrFail($id);
        return $repro->delete();
    }

    /**
     * Valida que el animal asociado al etapa_animal sea hembra ('H').
     */
    private function validateFemaleEtapaAnimal($etapaAnimalId): void
    {
        $etapaAnimal = EtapaAnimal::with('animal')->findOrFail($etapaAnimalId);
        if ($etapaAnimal->animal) {
            $sexo = strtoupper((string) $etapaAnimal->animal->sexo);
            if (!in_array($sexo, ['H', 'F', 'HEMBRA', 'FEMENINO'], true)) {
                throw ValidationException::withMessages([
                    'animal_etapa_id' => ['El registro de reproducción solo puede ser creado para animales hembras (H).']
                ]);
            }
        }
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
                'animal_id' => ['El registro de reproducción solo puede ser creado para animales hembras (H).']
            ]);
        }
    }
}
