<?php

namespace App\Services\Produccion;

use App\Models\Lactancia;
use App\Models\EtapaAnimal;
use App\Models\Animal;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

class LactanciaService
{
    /**
     * Retrieve paginated lactancias with applied filters and authorization.
     */
    public function getPaginatedLactancias(array $filters, $user, $perPage = 15)
    {
        $query = Lactancia::with(['animal', 'etapa', 'etapaAnimal', 'lecheRecords']);

        if (isset($filters['animal_id'])) {
            $query->forAnimal($filters['animal_id']);
        }

        if (isset($filters['activa'])) {
            if ($filters['activa'] == '1' || $filters['activa'] === true || $filters['activa'] === 'true') {
                $query->active();
            }
        }

        if (isset($filters['fecha_inicio'])) {
            $endDate = $filters['fecha_fin'] ?? null;
            $query->byDateRange($filters['fecha_inicio'], $endDate);
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
     * Create a new Lactancia record resolving animal_etapa_id and validating female sex.
     */
    public function createLactancia(array $data)
    {
        if (!isset($data['animal_etapa_id']) && isset($data['animal_id']) && isset($data['etapa_id'])) {
            $etapaAnimal = EtapaAnimal::where('animal_id', $data['animal_id'])
                ->where('etapa_id', $data['etapa_id'])
                ->first();

            if ($etapaAnimal) {
                $data['animal_etapa_id'] = $etapaAnimal->id;
            } else {
                throw ValidationException::withMessages([
                    'animal_etapa_id' => ['La combinación de animal y etapa especificada no existe en animal_etapa.']
                ]);
            }
        }

        if (isset($data['animal_etapa_id'])) {
            $this->validateFemaleEtapaAnimal($data['animal_etapa_id']);
        } elseif (isset($data['animal_id'])) {
            $this->validateFemaleAnimal($data['animal_id']);
        }

        $lactancia = Lactancia::create([
            'animal_etapa_id' => $data['animal_etapa_id'],
            'fecha_inicio'    => $data['fecha_inicio'],
            'fecha_fin'       => $data['fecha_fin'] ?? null,
            'secado'          => $data['secado'] ?? null,
        ]);

        return $lactancia->load(['animal', 'etapa', 'etapaAnimal', 'lecheRecords']);
    }

    /**
     * Fetch a specific Lactancia by ID with relationships.
     */
    public function getLactanciaById($id)
    {
        return Lactancia::with(['animal', 'etapa', 'etapaAnimal', 'lecheRecords'])->findOrFail($id);
    }

    /**
     * Update an existing Lactancia record.
     */
    public function updateLactancia($id, array $data)
    {
        $lactancia = Lactancia::findOrFail($id);

        if (!isset($data['animal_etapa_id']) && isset($data['animal_id']) && isset($data['etapa_id'])) {
            $etapaAnimal = EtapaAnimal::where('animal_id', $data['animal_id'])
                ->where('etapa_id', $data['etapa_id'])
                ->first();

            if ($etapaAnimal) {
                $data['animal_etapa_id'] = $etapaAnimal->id;
            } else {
                throw ValidationException::withMessages([
                    'animal_etapa_id' => ['La combinación de animal y etapa especificada no existe en animal_etapa.']
                ]);
            }
        }

        if (isset($data['animal_etapa_id']) && $data['animal_etapa_id'] != $lactancia->animal_etapa_id) {
            $this->validateFemaleEtapaAnimal($data['animal_etapa_id']);
        } elseif (isset($data['animal_id'])) {
            $this->validateFemaleAnimal($data['animal_id']);
        }

        $updatePayload = [];
        if (array_key_exists('animal_etapa_id', $data)) {
            $updatePayload['animal_etapa_id'] = $data['animal_etapa_id'];
        }
        if (array_key_exists('fecha_inicio', $data)) {
            $updatePayload['fecha_inicio'] = $data['fecha_inicio'];
        }
        if (array_key_exists('fecha_fin', $data)) {
            $updatePayload['fecha_fin'] = $data['fecha_fin'];
        }
        if (array_key_exists('secado', $data)) {
            $updatePayload['secado'] = $data['secado'];
        }

        $lactancia->update($updatePayload);

        return $lactancia->load(['animal', 'etapa', 'etapaAnimal', 'lecheRecords']);
    }

    /**
     * Delete an existing Lactancia record.
     */
    public function deleteLactancia($id)
    {
        $lactancia = Lactancia::findOrFail($id);
        return $lactancia->delete();
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
                    'animal_etapa_id' => ['El registro de lactancia solo puede ser asociado a animales hembras (H).']
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
                'animal_id' => ['El registro de lactancia solo puede ser asociado a animales hembras (H).']
            ]);
        }
    }
}
