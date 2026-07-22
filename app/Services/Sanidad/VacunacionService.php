<?php

namespace App\Services\Sanidad;

use App\Models\Animal;
use App\Models\Vacunacion;
use App\Models\VacunacionAnimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\Access\AuthorizationException;

class VacunacionService
{
    /**
     * Retrieve paginated vacunaciones with applied filters and user authorization.
     */
    public function getPaginatedVacunaciones(array $filters, $user, $perPage = 15)
    {
        $query = Vacunacion::with(['vacuna', 'rebano'])
            ->withCount('animales as animales_count');

        if (isset($filters['vacuna_id'])) {
            $query->forVacuna((int) $filters['vacuna_id']);
        }
        
        if (isset($filters['rebano_id'])) {
            $query->forRebano((int) $filters['rebano_id']);
        }
        
        if (isset($filters['fecha_inicio'])) {
            $query->where('fecha', '>=', $filters['fecha_inicio']);
        }

        if (isset($filters['fecha_fin'])) {
            $query->where('fecha', '<=', $filters['fecha_fin']);
        }

        // Authorization logic: Filter by propietario_id if the user is not an admin
        if (!$user->isAdmin() && $user->isPropietario()) {
            $propietario = $user->propietario;
            if ($propietario) {
                $query->whereHas('rebano.finca', function ($q) use ($propietario) {
                    $q->where('propietario_id', $propietario->id); // Asumiendo que la FK es propietario_id
                });
            }
        }

        if (isset($filters['nopaginate'])) {
            return $query->get();
        }

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * Retrieve eligible animals.
     */
    public function getAnimalesElegibles(int $rebanoId, ?string $sexo, ?int $etapaId)
    {
        $query = Animal::where('rebano_id', $rebanoId); // assuming V2 schema for animal->rebano relation

        if (method_exists(Animal::class, 'scopeActive')) {
            $query->active();
        }

        if (!empty($sexo)) {
            $query->where('sexo', $sexo);
        }

        if (!empty($etapaId)) {
            $query->whereHas('etapaAnimales', function ($q) use ($etapaId) {
                $q->where('etapa_id', $etapaId)
                    ->where(function ($sq) {
                        $sq->whereNull('fecha_fin')
                            ->orWhere('fecha_fin', '>', now()->toDateString());
                    });
            });
        }

        return $query->orderBy('nombre')
            ->get(['id', 'rebano_id', 'nombre', 'codigo_animal', 'sexo']);
    }

    /**
     * Create a new Vacunacion.
     */
    public function createVacunacion(array $data)
    {
        $animalIds = collect($data['animal_ids'] ?? [])->unique()->map(fn($id) => (int)$id)->all();

        return DB::transaction(function () use ($data, $animalIds) {
            $costo = (float) ($data['costo_dosis'] ?? 0);
            $totalAnimales = count($animalIds);

            $vacunacion = Vacunacion::create([
                'vacuna_id'      => $data['vacuna_id'],
                'casa_comercial_id' => $data['casa_comercial_id'] ?? null,
                'rebano_id'      => $data['rebano_id'],
                'modo_seleccion' => 'lista_animales',
                'filtros'        => $data['filtros'] ?? null,
                'fecha'          => $data['fecha'],
                'costo_dosis'    => $costo,
                'total_animales' => $totalAnimales,
                'monto_total'    => round($totalAnimales * $costo, 2),
                'observacion'    => $data['observacion'] ?? null,
            ]);

            $this->syncAnimales($vacunacion->id, $animalIds);

            return $this->getVacunacionById($vacunacion->id);
        });
    }

    /**
     * Fetch a specific Vacunacion by ID with its relationships.
     */
    public function getVacunacionById($id)
    {
        return Vacunacion::with([
            'vacuna',
            'rebano',
            'animales.animal',
        ])->withCount('animales as animales_count')->findOrFail($id);
    }

    /**
     * Update an existing Vacunacion.
     */
    public function updateVacunacion($id, array $data)
    {
        $vacunacion = Vacunacion::findOrFail($id);
        $animalIds = collect($data['animal_ids'] ?? [])->unique()->map(fn($itemId) => (int)$itemId)->all();

        DB::transaction(function () use ($vacunacion, $data, $animalIds) {
            $costo = (float) ($data['costo_dosis'] ?? 0);
            $totalAnimales = count($animalIds);

            $vacunacion->update([
                'vacuna_id'      => $data['vacuna_id'] ?? $vacunacion->vacuna_id,
                'casa_comercial_id' => $data['casa_comercial_id'] ?? $vacunacion->casa_comercial_id,
                'rebano_id'      => $data['rebano_id'] ?? $vacunacion->rebano_id,
                'modo_seleccion' => 'lista_animales',
                'filtros'        => $data['filtros'] ?? $vacunacion->filtros,
                'fecha'          => $data['fecha'] ?? $vacunacion->fecha,
                'costo_dosis'    => $costo,
                'total_animales' => $totalAnimales,
                'monto_total'    => round($totalAnimales * $costo, 2),
                'observacion'    => array_key_exists('observacion', $data) ? $data['observacion'] : $vacunacion->observacion,
            ]);

            VacunacionAnimal::where('vacunacion_id', $vacunacion->id)->delete();
            $this->syncAnimales($vacunacion->id, $animalIds);
        });

        return $this->getVacunacionById($vacunacion->id);
    }

    /**
     * Delete an existing Vacunacion.
     */
    public function deleteVacunacion($id)
    {
        $vacunacion = Vacunacion::findOrFail($id);
        $vacunacion->delete();
        return true;
    }

    /**
     * Helper to insert animal relations.
     */
    private function syncAnimales(int $vacunacionId, array $animalIds): void
    {
        if (empty($animalIds)) return;

        $rows = collect($animalIds)->map(fn ($animalId) => [
            'vacunacion_id' => $vacunacionId,
            'animal_id'     => $animalId,
            'created_at'    => now(),
            'updated_at'    => now(),
        ])->all();

        VacunacionAnimal::insert($rows);
    }
}
