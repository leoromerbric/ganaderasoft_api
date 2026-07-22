<?php

namespace App\Services\Sanidad;

use App\Models\CasaComercial;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CasaComercialService
{
    /**
     * Fetch a paginated list of Casas Comerciales based on filters.
     */
    public function getPaginatedCasasComerciales(array $filters, $perPage = 15)
    {
        $query = CasaComercial::query();

        if (!empty($filters['laboratorio'])) {
            $query->byLaboratorio($filters['laboratorio']);
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
     * Create a new CasaComercial.
     */
    public function createCasaComercial(array $data)
    {
        return CasaComercial::create([
            'laboratorio'     => $data['laboratorio'],
            'marca_comercial' => $data['marca_comercial'],
            'activa'          => array_key_exists('activa', $data) ? $data['activa'] : true,
        ]);
    }

    /**
     * Fetch a specific CasaComercial by ID with relationships.
     */
    public function getCasaComercialById($id)
    {
        // Removido 'dosis' ya que no existe la relación en el modelo V2
        return CasaComercial::with(['vacunas'])->findOrFail($id);
    }

    /**
     * Update an existing CasaComercial.
     */
    public function updateCasaComercial($id, array $data)
    {
        $casa = CasaComercial::findOrFail($id);

        $casa->update([
            'laboratorio'     => $data['laboratorio'] ?? $casa->laboratorio,
            'marca_comercial' => $data['marca_comercial'] ?? $casa->marca_comercial,
            'activa'          => array_key_exists('activa', $data) ? $data['activa'] : $casa->activa,
        ]);

        return $casa;
    }

    /**
     * Delete an existing CasaComercial.
     */
    public function deleteCasaComercial($id)
    {
        $casa = CasaComercial::findOrFail($id);
        $casa->delete();
        return true;
    }
}
