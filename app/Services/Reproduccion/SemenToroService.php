<?php

namespace App\Services\Reproduccion;

use App\Models\SemenToro;
use App\Models\Animal;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

class SemenToroService
{
    /**
     * Retrieve paginated semen records with applied filters and authorization.
     */
    public function getPaginatedSemen(array $filters, $user, $perPage = 15)
    {
        $query = SemenToro::with('toro');

        if (isset($filters['toro_id'])) {
            $query->forToro($filters['toro_id']);
        } elseif (isset($filters['animal_id'])) {
            $query->forToro($filters['animal_id']);
        }

        if (isset($filters['activo'])) {
            if ($filters['activo'] == '1' || $filters['activo'] === true || $filters['activo'] === 'true') {
                $query->activo();
            }
        }

        if (isset($filters['nopaginate'])) {
            return $query->get();
        }

        // Control de acceso para propietarios no-administradores
        if (!$user->isAdmin() && $user->isPropietario()) {
            $propietario = $user->propietario;
            if ($propietario) {
                $query->whereHas('toro.rebano.finca', function ($q) use ($propietario) {
                    $q->where('propietario_id', $propietario->id);
                });
            }
        }

        return $query->paginate($perPage);
    }

    /**
     * Create a new SemenToro record.
     */
    public function createSemen(array $data)
    {
        if (isset($data['animal_id'])) {
            $this->validateToro($data['animal_id']);
        }

        $semen = SemenToro::create([
            'animal_id' => $data['animal_id'],
            'estado'    => $data['estado'] ?? true,
            'fecha'     => $data['fecha'] ?? null,
        ]);

        return $semen->load('toro');
    }

    /**
     * Fetch a specific SemenToro by ID with relationships.
     */
    public function getSemenById($id)
    {
        return SemenToro::with(['toro', 'servicios'])->findOrFail($id);
    }

    /**
     * Update an existing SemenToro record.
     */
    public function updateSemen($id, array $data)
    {
        $semen = SemenToro::findOrFail($id);

        if (isset($data['animal_id']) && $data['animal_id'] != $semen->animal_id) {
            $this->validateToro($data['animal_id']);
        }

        $updatePayload = [];
        if (array_key_exists('animal_id', $data)) {
            $updatePayload['animal_id'] = $data['animal_id'];
        }
        if (array_key_exists('estado', $data)) {
            $updatePayload['estado'] = $data['estado'];
        }
        if (array_key_exists('fecha', $data)) {
            $updatePayload['fecha'] = $data['fecha'];
        }

        $semen->update($updatePayload);

        return $semen->load('toro');
    }

    /**
     * Delete an existing SemenToro record.
     */
    public function deleteSemen($id)
    {
        $semen = SemenToro::findOrFail($id);
        return $semen->delete();
    }

    /**
     * Valida que el animal sea un toro (macho y en etapa adulta reproductora como Toro o Butoro).
     */
    private function validateToro($animalId): void
    {
        $animal = Animal::with(['etapaActual.etapa', 'etapaAnimales.etapa'])->findOrFail($animalId);
        $sexo = strtoupper((string) $animal->sexo);

        if (!in_array($sexo, ['M', 'MACHO', 'MASCULINO'], true)) {
            throw ValidationException::withMessages([
                'animal_id' => ['El registro de semen solo puede ser asociado a animales machos (M).']
            ]);
        }

        $etapaAnimal = $animal->etapaActual ?? $animal->etapaAnimales->sortByDesc('fecha_ini')->first();

        if ($etapaAnimal && $etapaAnimal->etapa) {
            $nombreEtapa = strtoupper(trim((string) $etapaAnimal->etapa->nombre));
            $etapasPermitidas = ['TORO', 'BUTORO', 'TORETE', 'SEMENTAL', 'REPRODUCTOR'];

            $esToro = false;
            foreach ($etapasPermitidas as $permitida) {
                if (str_contains($nombreEtapa, $permitida)) {
                    $esToro = true;
                    break;
                }
            }

            if (!$esToro) {
                throw ValidationException::withMessages([
                    'animal_id' => ["El animal seleccionado se encuentra en la etapa '{$etapaAnimal->etapa->nombre}'. El registro de semen solo puede ser asociado a un animal en etapa de Toro, Butoro o Semental reproductor."]
                ]);
            }
        }
    }
}
