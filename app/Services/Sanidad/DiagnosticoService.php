<?php

namespace App\Services\Sanidad;

use App\Models\Diagnostico;
use App\Models\EtapaAnimal;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\Access\AuthorizationException;

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

        if (isset($filters['nopaginate'])) {
            return $query->get();
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
        if (!isset($data['animal_etapa_id']) && isset($data['animal_id']) && isset($data['etapa_id'])) {
            $etapaAnimal = EtapaAnimal::where('animal_id', $data['animal_id'])
                ->where('etapa_id', $data['etapa_id'])
                ->first();
            
            if ($etapaAnimal) {
                $data['animal_etapa_id'] = $etapaAnimal->id;
            }
        }

        $diagnostico = Diagnostico::create($data);
        return $diagnostico->load(['etapaAnimal.animal', 'etapaAnimal.etapa', 'tratamientos']);
    }

    /**
     * Fetch a specific Diagnostico by ID with its relationships.
     */
    public function getDiagnosticoById($id)
    {
        return Diagnostico::with(['etapaAnimal.animal', 'etapaAnimal.etapa', 'tratamientos'])->findOrFail($id);
    }

    /**
     * Update an existing Diagnostico.
     */
    public function updateDiagnostico($id, array $data)
    {
        $diagnostico = Diagnostico::findOrFail($id);

        if (!isset($data['animal_etapa_id']) && isset($data['animal_id']) && isset($data['etapa_id'])) {
            $etapaAnimal = EtapaAnimal::where('animal_id', $data['animal_id'])
                ->where('etapa_id', $data['etapa_id'])
                ->first();
            
            if ($etapaAnimal) {
                $data['animal_etapa_id'] = $etapaAnimal->id;
            }
        }

        $diagnostico->update($data);
        return $diagnostico->load(['etapaAnimal.animal', 'etapaAnimal.etapa', 'tratamientos']);
    }

    /**
     * Delete an existing Diagnostico.
     */
    public function deleteDiagnostico($id)
    {
        $diagnostico = Diagnostico::findOrFail($id);
        return $diagnostico->delete();
    }
}