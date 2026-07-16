<?php

namespace App\Services\Sanidad;

use App\Models\Diagnostico;

class DiagnosticoService
{
    /**
     * Retrieve paginated diagnosticos with applied filters and user authorization.
     */
    public function getPaginatedDiagnosticos(array $filters, $user, $perPage = 15)
    {
        $query = Diagnostico::with(['etapaAnimal.animal', 'etapaAnimal.etapa', 'tratamientos']);

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

        // Adapted authorization logic targeting the propietario_id column on fincas
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
     * Create a new Diagnostico.
     */
    public function createDiagnostico(array $data)
    {
        $diagnostico = Diagnostico::create($data);
        return $diagnostico->load('etapaAnimal.animal');
    }

    /**
     * Fetch a specific Diagnostico by ID with its relationships.
     */
    public function getDiagnosticoById($id)
    {
        return Diagnostico::with(['etapaAnimal.animal', 'etapaAnimal.etapa', 'tratamientos'])->find($id);
    }

    /**
     * Update an existing Diagnostico.
     */
    public function updateDiagnostico($id, array $data)
    {
        $diagnostico = Diagnostico::find($id);
        
        if (!$diagnostico) {
            return null;
        }

        $diagnostico->update($data);
        return $diagnostico;
    }

    /**
     * Delete an existing Diagnostico.
     */
    public function deleteDiagnostico($id)
    {
        $diagnostico = Diagnostico::find($id);
        
        if (!$diagnostico) {
            return false;
        }
        
        $diagnostico->delete();
        return true;
    }
}