<?php

namespace App\Services\Sanidad;

use App\Models\Tratamiento;

class TratamientoService
{
    /**
     * Retrieve paginated tratamientos with applied filters and user authorization.
     */
    public function getPaginatedTratamientos(array $filters, $user, $perPage = 15)
    {
        $query = Tratamiento::with(['diagnostico.etapaAnimal.animal']);

        if (isset($filters['diagnostico_id'])) {
            $query->where('diagnostico_id', $filters['diagnostico_id']);
        }
        
        if (isset($filters['fecha_inicio'])) {
            $fechaFin = $filters['fecha_fin'] ?? date('Y-m-d');
            $query->whereBetween('fecha_ini', [$filters['fecha_inicio'], $fechaFin]);
        }

        // Authorization logic: Filter by propietario_id if the user is not an admin
        if (!$user->isAdmin() && $user->isPropietario()) {
            $propietario = $user->propietario;
            if ($propietario) {
                $query->whereHas('diagnostico.etapaAnimal.animal.rebano.finca', function ($q) use ($propietario) {
                    $q->where('propietario_id', $propietario->id);
                });
            }
        }

        return $query->paginate($perPage);
    }

    /**
     * Create a new Tratamiento.
     */
    public function createTratamiento(array $data)
    {
        $tratamiento = Tratamiento::create($data);
        return $tratamiento->load('diagnostico');
    }

    /**
     * Fetch a specific Tratamiento by ID with its relationships.
     */
    public function getTratamientoById($id)
    {
        return Tratamiento::with(['diagnostico.etapaAnimal.animal'])->find($id);
    }

    /**
     * Update an existing Tratamiento.
     */
    public function updateTratamiento($id, array $data)
    {
        $tratamiento = Tratamiento::find($id);

        if (!$tratamiento) {
            return null;
        }

        $tratamiento->update($data);
        return $tratamiento;
    }

    /**
     * Delete an existing Tratamiento.
     */
    public function deleteTratamiento($id)
    {
        $tratamiento = Tratamiento::find($id);

        if (!$tratamiento) {
            return false;
        }
        
        $tratamiento->delete();
        return true;
    }
}