<?php

namespace App\Services\Produccion;

use App\Models\Leche;
use App\Models\Lactancia;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

class LecheService
{
    /**
     * Retrieve paginated leche records with applied filters and authorization.
     */
    public function getPaginatedLeche(array $filters, $user, $perPage = 15)
    {
        $query = Leche::with(['lactancia.animal', 'lactancia.etapa']);

        if (isset($filters['lactancia_id'])) {
            $query->forLactancia($filters['lactancia_id']);
        }

        if (isset($filters['fecha_inicio'])) {
            $endDate = $filters['fecha_fin'] ?? null;
            $query->byDateRange($filters['fecha_inicio'], $endDate);
        }

        if (isset($filters['produccion_minima'])) {
            $query->minProduction($filters['produccion_minima']);
        }

        if (isset($filters['nopaginate'])) {
            return $query->get();
        }

        if (!$user->isAdmin() && $user->isPropietario()) {
            $propietario = $user->propietario;
            if ($propietario) {
                $query->whereHas('lactancia.animal.rebano.finca', function ($q) use ($propietario) {
                    $q->where('propietario_id', $propietario->id);
                });
            }
        }

        return $query->paginate($perPage);
    }

    /**
     * Create a new Leche record checking permissions and loading relations.
     */
    public function createLeche(array $data, $user)
    {
        $lactancia = Lactancia::with(['animal.rebano.finca'])->find($data['lactancia_id']);

        if (!$lactancia) {
            throw new ModelNotFoundException('Lactancia no encontrada');
        }

        if (!$user->isAdmin()) {
            if ($user->isPropietario()) {
                $propietario = $user->propietario;
                $ownerId = optional(optional(optional($lactancia->animal)->rebano)->finca)->propietario_id;
                if (!$propietario || !$ownerId || $ownerId !== $propietario->id) {
                    throw new AuthorizationException('No tiene permisos para registrar leche a esta lactancia');
                }
            } else {
                throw new AuthorizationException('No tiene permisos para registrar leche');
            }
        }

        $leche = Leche::create([
            'lactancia_id' => $data['lactancia_id'],
            'fecha_pesaje' => $data['fecha_pesaje'],
            'pesaje_total' => $data['pesaje_total'],
        ]);

        return $leche->load(['lactancia.animal', 'lactancia.etapa']);
    }

    /**
     * Fetch a specific Leche record by ID with relationships and permission check.
     */
    public function getLecheById($id, $user)
    {
        $leche = Leche::with(['lactancia.animal', 'lactancia.etapa'])->findOrFail($id);

        $this->checkPermissions($leche, $user, 'ver este registro de leche');

        return $leche;
    }

    /**
     * Update an existing Leche record.
     */
    public function updateLeche($id, array $data, $user)
    {
        $leche = Leche::with(['lactancia.animal.rebano.finca'])->findOrFail($id);

        $this->checkPermissions($leche, $user, 'editar este registro de leche');

        $leche->update($data);

        return $leche->load(['lactancia.animal', 'lactancia.etapa']);
    }

    /**
     * Delete a Leche record.
     */
    public function deleteLeche($id, $user)
    {
        $leche = Leche::with(['lactancia.animal.rebano.finca'])->findOrFail($id);

        $this->checkPermissions($leche, $user, 'eliminar este registro de leche');

        return $leche->delete();
    }

    /**
     * Check if user has permission to interact with the Leche record.
     */
    protected function checkPermissions(Leche $leche, $user, string $actionMessage)
    {
        if (!$user->isAdmin()) {
            if ($user->isPropietario()) {
                $propietario = $user->propietario;
                $ownerId = optional(optional(optional(optional($leche->lactancia)->animal)->rebano)->finca)->propietario_id;
                if (!$propietario || !$ownerId || $ownerId !== $propietario->id) {
                    throw new AuthorizationException("No tiene permisos para {$actionMessage}");
                }
            } else {
                throw new AuthorizationException("No tiene permisos para {$actionMessage}");
            }
        }
    }
}
