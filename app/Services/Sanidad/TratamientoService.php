<?php

namespace App\Services\Sanidad;

use App\Models\Tratamiento;
use Illuminate\Auth\Access\AuthorizationException;

use App\Services\BaseService;

class TratamientoService extends BaseService
{
    /**
     * Obtiene una lista paginada de tratamientos basándose en los filtros.
     */
    public function getPaginatedTratamientos(array $filters, $user = null, $perPage = 15)
    {
        $user = $user ?? auth()->user();

        if ($user->cannot('readAny', Tratamiento::class)) {
            throw new AuthorizationException('Sin permisos para listar tratamientos.');
        }

        $query = Tratamiento::with(['diagnostico.etapaAnimal.animal']);

        if (isset($filters['diagnostico_id'])) {
            $query->where('diagnostico_id', $filters['diagnostico_id']);
        }
        
        if (isset($filters['fecha_inicio'])) {
            $fechaFin = $filters['fecha_fin'] ?? date('Y-m-d');
            $query->whereBetween('fecha_ini', [$filters['fecha_inicio'], $fechaFin]);
        }

        $this->applyFincaFilter($query, $user, 'diagnostico.animal.rebano');
        return $query->paginate($perPage);
    }

    /**
     * Crea un nuevo registro de tratamiento resolviendo animal_etapa_id si es necesario.
     */
    public function createTratamiento(array $data, $user = null)
    {
        $user = $user ?? auth()->user();
        $diagnosticoId = (int) $data['diagnostico_id'];

        if ($user->cannot('create', [Tratamiento::class, $diagnosticoId])) {
            throw new AuthorizationException('No tiene permisos para registrar tratamiento a este diagnóstico.');
        }

        $tratamiento = Tratamiento::create($data);
        return $tratamiento->load('diagnostico');
    }

    /**
     * Obtiene un tratamiento específico por su ID.
     */
    public function getTratamientoById($id, $user = null)
    {
        $user = $user ?? auth()->user();
        $tratamiento = Tratamiento::with(['diagnostico.etapaAnimal.animal'])->findOrFail($id);

        if ($user->cannot('read', $tratamiento)) {
            throw new AuthorizationException('No tiene permisos para ver este tratamiento.');
        }

        return $tratamiento;
    }

    /**
     * Actualiza un registro de tratamiento existente.
     */
    public function updateTratamiento($id, array $data, $user = null)
    {
        $user = $user ?? auth()->user();
        $tratamiento = Tratamiento::findOrFail($id);

        if ($user->cannot('update', $tratamiento)) {
            throw new AuthorizationException('No tiene permisos para actualizar este tratamiento.');
        }

        $tratamiento->update($data);
        return $tratamiento->load(['diagnostico.etapaAnimal.animal']);
    }

    /**
     * Elimina un registro de tratamiento existente.
     */
    public function deleteTratamiento($id, $user = null)
    {
        $user = $user ?? auth()->user();
        $tratamiento = Tratamiento::findOrFail($id);
        
        if ($user->cannot('delete', $tratamiento)) {
            throw new AuthorizationException('No tiene permisos para eliminar este tratamiento.');
        }

        $tratamiento->delete();
        return true;
    }
}