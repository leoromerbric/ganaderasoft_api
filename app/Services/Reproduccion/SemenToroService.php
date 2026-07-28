<?php

namespace App\Services\Reproduccion;

use App\Models\SemenToro;
use App\Models\Animal;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

use App\Services\BaseService;

class SemenToroService extends BaseService
{
    /**
     * Obtiene una lista paginada de registros de semen basándose en los filtros y la autorización del usuario.
     */
    public function getPaginatedSemen(array $filters, $user = null, $perPage = 15)
    {
        $user = $user ?? auth()->user();

        if ($user->cannot('readAny', SemenToro::class)) {
            throw new AuthorizationException('Sin permisos para listar semen de toros.');
        }

        $query = SemenToro::with('toro');

        if (isset($filters['toro_id'])) {
            $query->forToro($filters['toro_id']);
        } elseif (isset($filters['animal_id'])) {
            $query->forToro($filters['animal_id']);
        }

        if (isset($filters['activo'])) {
            if ($filters['activo'] == '1' || $filters['activo'] === true || $filters['activo'] === 'true') {
                $query->activo();
            }
        }

        $this->applyFincaFilter($query, $user, 'toro.rebano');

        if (isset($filters['nopaginate'])) {
            return $query->get();
        }

        return $query->paginate($perPage);
    }

    /**
     * Crea un nuevo registro de SemenToro.
     */
    public function createSemen(array $data, $user = null)
    {
        $user = $user ?? auth()->user();
        $animalId = $data['animal_id'] ?? null;

        if ($user->cannot('create', [SemenToro::class, $animalId])) {
            throw new AuthorizationException('No tiene permisos para registrar semen a este toro.');
        }

        if (isset($data['animal_id'])) {
            $this->validateToro($data['animal_id']);
        }

        $semen = SemenToro::create([
            'animal_id' => $data['animal_id'],
            'estado'    => $data['estado'] ?? true,
            'fecha'     => $data['fecha'] ?? null,
        ]);

        return $semen->load('toro');
    }

    /**
     * Obtiene un registro de SemenToro específico por su ID.
     */
    public function getSemenById($id, $user = null)
    {
        $user = $user ?? auth()->user();
        $semen = SemenToro::with(['toro', 'servicios'])->findOrFail($id);

        if ($user->cannot('read', $semen)) {
            throw new AuthorizationException('No tiene permisos para ver este registro de semen.');
        }

        return $semen;
    }

    /**
     * Actualiza un registro de SemenToro existente.
     */
    public function updateSemen($id, array $data, $user = null)
    {
        $user = $user ?? auth()->user();
        $semen = SemenToro::findOrFail($id);

        if ($user->cannot('update', $semen)) {
            throw new AuthorizationException('No tiene permisos para actualizar este registro de semen.');
        }

        if (isset($data['animal_id']) && $data['animal_id'] != $semen->animal_id) {
            if ($user->cannot('create', [SemenToro::class, (int) $data['animal_id']])) {
                throw new AuthorizationException('No tiene permisos para asignar semen a ese nuevo toro.');
            }
            $this->validateToro($data['animal_id']);
        }

        $updatePayload = [];
        if (array_key_exists('animal_id', $data)) {
            $updatePayload['animal_id'] = $data['animal_id'];
        }
        if (array_key_exists('estado', $data)) {
            $updatePayload['estado'] = $data['estado'];
        }
        if (array_key_exists('fecha', $data)) {
            $updatePayload['fecha'] = $data['fecha'];
        }

        $semen->update($updatePayload);

        return $semen->load('toro');
    }

    /**
     * Elimina un registro de SemenToro existente.
     */
    public function deleteSemen($id, $user = null)
    {
        $user = $user ?? auth()->user();
        $semen = SemenToro::findOrFail($id);

        if ($user->cannot('delete', $semen)) {
            throw new AuthorizationException('No tiene permisos para eliminar este registro de semen.');
        }

        return $semen->delete();
    }

    /**
     * Valida que el animal sea un toro (macho y en etapa adulta reproductora como Toro o Butoro).
     */
    private function validateToro($animalId): void
    {
        $animal = Animal::with(['etapaActual.etapa', 'etapaAnimales.etapa'])->findOrFail($animalId);
        $sexo = strtoupper((string) $animal->sexo);

        if (!in_array($sexo, ['M', 'MACHO', 'MASCULINO'], true)) {
            throw ValidationException::withMessages([
                'animal_id' => ['El registro de semen solo puede ser asociado a animales machos (M).']
            ]);
        }

        $etapaAnimal = $animal->etapaActual ?? $animal->etapaAnimales->sortByDesc('fecha_ini')->first();

        if ($etapaAnimal && $etapaAnimal->etapa) {
            $nombreEtapa = strtoupper(trim((string) $etapaAnimal->etapa->nombre));
            $etapasPermitidas = ['TORO', 'BUTORO', 'TORETE', 'SEMENTAL', 'REPRODUCTOR'];

            $esToro = false;
            foreach ($etapasPermitidas as $permitida) {
                if (str_contains($nombreEtapa, $permitida)) {
                    $esToro = true;
                    break;
                }
            }

            if (!$esToro) {
                throw ValidationException::withMessages([
                    'animal_id' => ["El animal seleccionado se encuentra en la etapa '{$etapaAnimal->etapa->nombre}'. El registro de semen solo puede ser asociado a un animal en etapa de Toro, Butoro o Semental reproductor."]
                ]);
            }
        }
    }
}
