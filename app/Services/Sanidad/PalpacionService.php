<?php

namespace App\Services\Sanidad;

use App\Models\Palpacion;
use App\Models\EtapaAnimal;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\Access\AuthorizationException;

class PalpacionService
{
    /**
     * Retrieve paginated palpaciones with applied filters and user authorization.
     */
    public function getPaginatedPalpaciones(array $filters, $user, $perPage = 15)
    {
        $query = Palpacion::with(['etapaAnimal.animal', 'etapaAnimal.etapa', 'tecnico']);

        if (isset($filters['animal_id'])) {
            $query->whereHas('etapaAnimal', function($q) use ($filters) {
                $q->where('animal_id', $filters['animal_id']);
            });
        }
        
        if (isset($filters['tipo'])) {
            $query->where('tipo', $filters['tipo']);
        }
        
        if (isset($filters['fecha_inicio'])) {
            $fechaFin = $filters['fecha_fin'] ?? date('Y-m-d');
            $query->whereBetween('fecha', [$filters['fecha_inicio'], $fechaFin]);
        }

        if (isset($filters['nopaginate'])) {
            return $query->get();
        }

        // Authorization logic: Filter by propietario_id if the user is not an admin
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
     * Create a new Palpacion.
     */
    public function createPalpacion(array $data)
    {
        if (!isset($data['animal_etapa_id']) && isset($data['animal_id']) && isset($data['etapa_id'])) {
            $etapaAnimal = EtapaAnimal::where('animal_id', $data['animal_id'])
                ->where('etapa_id', $data['etapa_id'])
                ->first();
            
            if ($etapaAnimal) {
                $data['animal_etapa_id'] = $etapaAnimal->id;
            } else {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'animal_etapa_id' => ['La combinación de animal y etapa no existe en los registros.']
                ]);
            }
        }

        $palpacion = Palpacion::create($data);
        return $palpacion->load(['etapaAnimal.animal', 'etapaAnimal.etapa', 'tecnico']);
    }

    /**
     * Fetch a specific Palpacion by ID with its relationships.
     */
    public function getPalpacionById($id)
    {
        return Palpacion::with(['etapaAnimal.animal', 'etapaAnimal.etapa', 'tecnico'])->findOrFail($id);
    }

    /**
     * Update an existing Palpacion.
     */
    public function updatePalpacion($id, array $data)
    {
        $palpacion = Palpacion::findOrFail($id);

        if (!isset($data['animal_etapa_id']) && isset($data['animal_id']) && isset($data['etapa_id'])) {
            $etapaAnimal = EtapaAnimal::where('animal_id', $data['animal_id'])
                ->where('etapa_id', $data['etapa_id'])
                ->first();
            
            if ($etapaAnimal) {
                $data['animal_etapa_id'] = $etapaAnimal->id;
            } else {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'animal_etapa_id' => ['La combinación de animal y etapa no existe en los registros.']
                ]);
            }
        }

        $palpacion->update($data);
        return $palpacion->load(['etapaAnimal.animal', 'etapaAnimal.etapa', 'tecnico']);
    }

    /**
     * Delete an existing Palpacion.
     */
    public function deletePalpacion($id)
    {
        $palpacion = Palpacion::findOrFail($id);
        return $palpacion->delete();
    }
}