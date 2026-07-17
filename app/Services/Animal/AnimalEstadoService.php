<?php

namespace App\Services\Animal;

use App\Models\Animal;
use App\Models\EstadoAnimal;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AnimalEstadoService
{
    /**
     * Registra un nuevo estado de salud para el animal.
     * Cierra automáticamente estados de salud anteriores si el nuevo estado es el activo actualmente.
     *
     * @param int $animalId ID del animal.
     * @param array $data Datos del estado de salud.
     * @param mixed $user Usuario que realiza la acción.
     * @return EstadoAnimal
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function createEstado(int $animalId, array $data, $user): EstadoAnimal
    {
        $animal = Animal::findOrFail($animalId);

        // Control de permisos para no administradores
        if (!$user->isAdmin()) {
            $propietario = $user->propietario;
            if (!$propietario || $animal->rebano->finca->propietario_id != $propietario->id) {
                throw new AuthorizationException('No tiene permisos para modificar el estado de salud de este animal.');
            }
        }

        // Si se registra un estado sin fecha de fin (activo), cerramos el estado activo anterior
        if (empty($data['fecha_fin'])) {
            EstadoAnimal::where('animal_id', $animal->id)
                ->whereNull('fecha_fin')
                ->update(['fecha_fin' => now()->toDateString()]);
        }

        $estadoAnimal = EstadoAnimal::create([
            'fecha_ini'       => $data['fecha_ini'],
            'fecha_fin'       => $data['fecha_fin'] ?? null,
            'estado_salud_id' => $data['estado_salud_id'],
            'animal_id'       => $animal->id,
        ]);

        return $estadoAnimal->load(['estadoSalud', 'animal']);
    }

    /**
     * Actualiza un registro del historial de estados de salud.
     *
     * @param int $animalId ID del animal.
     * @param int $estadoId ID del registro de EstadoAnimal a actualizar.
     * @param array $data Datos a actualizar.
     * @param mixed $user Usuario.
     * @return EstadoAnimal
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function updateEstado(int $id, array $data, $user): EstadoAnimal
    {
        // Buscar el registro histórico
        $estadoAnimal = EstadoAnimal::with('animal.rebano.finca')->findOrFail($id);

        // Control de permisos
        if (!$user->isAdmin()) {
            $propietario = $user->propietario;
            if (!$propietario || $estadoAnimal->animal->rebano->finca->propietario_id != $propietario->id) {
                throw new AuthorizationException('No tiene permisos para actualizar este estado de salud.');
            }
        }

        // Mapeo selectivo de atributos
        $updatePayload = [];
        if (array_key_exists('fecha_ini', $data)) $updatePayload['fecha_ini'] = $data['fecha_ini'];
        if (array_key_exists('fecha_fin', $data)) $updatePayload['fecha_fin'] = $data['fecha_fin'];
        if (array_key_exists('estado_salud_id', $data)) $updatePayload['estado_salud_id'] = $data['estado_salud_id'];

        $estadoAnimal->update($updatePayload);

        return $estadoAnimal->load(['estadoSalud', 'animal']);
    }
}
