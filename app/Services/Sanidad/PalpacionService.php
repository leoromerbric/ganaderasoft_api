<?php

namespace App\Services\Sanidad;

use App\Models\Palpacion;

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
        $palpacion = Palpacion::create($data);
        return $palpacion->load(['etapaAnimal.animal', 'tecnico']);
    }

    /**
     * Fetch a specific Palpacion by ID with its relationships.
     */
    public function getPalpacionById($id)
    {
        return Palpacion::with(['etapaAnimal.animal', 'etapaAnimal.etapa', 'tecnico'])->find($id);
    }

    /**
     * Update an existing Palpacion.
     */
    public function updatePalpacion($id, array $data)
    {
        $palpacion = Palpacion::find($id);

        if (!$palpacion) {
            return null;
        }

        $palpacion->update($data);
        return $palpacion;
    }

    /**
     * Delete an existing Palpacion.
     */
    public function deletePalpacion($id)
    {
        $palpacion = Palpacion::find($id);

        if (!$palpacion) {
            return false;
        }
        
        $palpacion->delete();
        return true;
    }
}