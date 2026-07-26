<?php

namespace App\Services\Reproduccion;

use App\Models\RegistroCelo;
use App\Models\EtapaAnimal;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

class RegistroCeloService
{
    /**
     * Retrieve paginated registros de celo with applied filters and authorization.
     */
    public function getPaginatedCelos(array $filters, $user, $perPage = 15)
    {
        $query = RegistroCelo::with(['etapaAnimal.animal', 'etapaAnimal.etapa']);

        if (isset($filters['animal_id'])) {
            $query->whereHas('etapaAnimal', function ($q) use ($filters) {
                $q->where('animal_id', $filters['animal_id']);
            });
        }

        if (isset($filters['fecha_inicio'])) {
            $fechaFin = $filters['fecha_fin'] ?? date('Y-m-d');
            $query->whereBetween('fecha', [$filters['fecha_inicio'], $fechaFin]);
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
     * Create a new Registro de Celo resolving animal_etapa_id if needed.
     */
    public function createCelo(array $data)
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

        $celo = RegistroCelo::create([
            'animal_etapa_id' => $data['animal_etapa_id'],
            'fecha'           => $data['fecha'],
            'observacion'     => $data['observacion'] ?? null,
        ]);

        return $celo->load(['etapaAnimal.animal', 'etapaAnimal.etapa']);
    }

    /**
     * Fetch a specific RegistroCelo by ID with relationships.
     */
    public function getCeloById($id)
    {
        return RegistroCelo::with(['etapaAnimal.animal', 'etapaAnimal.etapa', 'servicios'])->findOrFail($id);
    }

    /**
     * Update an existing RegistroCelo.
     */
    public function updateCelo($id, array $data)
    {
        $celo = RegistroCelo::findOrFail($id);

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

        $updatePayload = [];
        if (array_key_exists('animal_etapa_id', $data)) {
            $updatePayload['animal_etapa_id'] = $data['animal_etapa_id'];
        }
        if (array_key_exists('fecha', $data)) {
            $updatePayload['fecha'] = $data['fecha'];
        }
        if (array_key_exists('observacion', $data)) {
            $updatePayload['observacion'] = $data['observacion'];
        }

        $celo->update($updatePayload);

        return $celo->load(['etapaAnimal.animal', 'etapaAnimal.etapa', 'servicios']);
    }

    /**
     * Delete an existing RegistroCelo.
     */
    public function deleteCelo($id)
    {
        $celo = RegistroCelo::findOrFail($id);
        return $celo->delete();
    }
}
