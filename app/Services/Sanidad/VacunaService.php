<?php

namespace App\Services\Sanidad;

use App\Models\Vacuna;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class VacunaService
{
    /**
     * Fetch a paginated list of vacunas based on filters.
     */
    public function getPaginatedVacunas(array $filters, $perPage = 15)
    {
        $query = Vacuna::with('casasComerciales');

        if (!empty($filters['nombre'])) {
            $query->byNombre($filters['nombre']);
        }

        if (isset($filters['activa'])) {
            $query->where('activa', filter_var($filters['activa'], FILTER_VALIDATE_BOOLEAN));
        }

        if (isset($filters['nopaginate'])) {
            return $query->get();
        }

        return $query->paginate($perPage);
    }

    /**
     * Create a new Vacuna.
     */
    public function createVacuna(array $data)
    {
        return Vacuna::create([
            'nombre'      => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? null,
            'activa'      => array_key_exists('activa', $data) ? $data['activa'] : true,
        ]);
    }

    /**
     * Fetch a specific Vacuna by ID with relationships.
     */
    public function getVacunaById($id)
    {
        return Vacuna::with(['casasComerciales'])->findOrFail($id);
    }

    /**
     * Update an existing Vacuna.
     */
    public function updateVacuna($id, array $data)
    {
        $vacuna = Vacuna::findOrFail($id);

        $vacuna->update([
            'nombre'      => $data['nombre'] ?? $vacuna->nombre,
            'descripcion' => array_key_exists('descripcion', $data) ? $data['descripcion'] : $vacuna->descripcion,
            'activa'      => array_key_exists('activa', $data) ? $data['activa'] : $vacuna->activa,
        ]);

        return $vacuna;
    }

    /**
     * Delete an existing Vacuna.
     */
    public function deleteVacuna($id)
    {
        $vacuna = Vacuna::findOrFail($id);
        $vacuna->delete();
        return true;
    }
}
