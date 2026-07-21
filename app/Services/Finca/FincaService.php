<?php

namespace App\Services\Finca;

use App\Models\Finca;
use App\Models\Terreno;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class FincaService
{
    /**
     * List all fincas with pagination based on user permissions.
     *
     * @param array $filters
     * @param User $user
     * @return LengthAwarePaginator
     * @throws AuthorizationException
     */
    public function listFincas(array $filters, User $user): LengthAwarePaginator
    {
        $query = Finca::with(['propietario.persona.users', 'terreno'])->active();

        if ($user->isAdmin()) {
            return $query->paginate(15);
        }

        $propietario = $user->propietario;
        if (!$propietario) {
            throw new AuthorizationException('El usuario no está registrado como propietario.');
        }

        return $query->forPropietario($propietario->id)->paginate(15);
    }

    /**
     * Store a newly created finca.
     *
     * @param array $data
     * @param User $user
     * @return Finca
     * @throws AuthorizationException
     */
    public function storeFinca(array $data, User $user): Finca
    {
        $propietarioId = (int) $data['propietario_id'];

        if (!$user->isAdmin()) {
            $propietario = $user->propietario;
            if (!$propietario || $propietario->id !== $propietarioId) {
                throw new AuthorizationException('No tiene permisos para crear finca para este propietario.');
            }
        }

        return DB::transaction(function () use ($data) {
            $finca = Finca::create([
                'propietario_id' => $data['propietario_id'],
                'nombre' => $data['nombre'],
                'explotacion_tipo' => $data['explotacion_tipo'],
                'archivado' => false,
            ]);

            if (!empty($data['terreno'])) {
                $terrenoData = $data['terreno'];
                $terrenoData['finca_id'] = $finca->id;
                Terreno::create($terrenoData);
            }

            return $finca->load(['propietario.persona.users', 'terreno']);
        });
    }

    /**
     * Get a specific finca detailing permissions.
     *
     * @param int $id
     * @param User $user
     * @return Finca
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function getFinca(int $id, User $user): Finca
    {
        $finca = Finca::with(['propietario.persona.users', 'terreno'])->findOrFail($id);

        if (!$user->isAdmin()) {
            $propietario = $user->propietario;
            if (!$propietario || $finca->propietario_id !== $propietario->id) {
                throw new AuthorizationException('No tiene permisos para ver esta finca.');
            }
        }

        return $finca;
    }

    /**
     * Update a finca.
     *
     * @param int $id
     * @param array $data
     * @param User $user
     * @return Finca
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function updateFinca(int $id, array $data, User $user): Finca
    {
        $finca = Finca::with(['propietario.persona.users', 'terreno'])->findOrFail($id);

        if (!$user->isAdmin()) {
            $propietario = $user->propietario;
            if (!$propietario || $finca->propietario_id !== $propietario->id) {
                throw new AuthorizationException('No tiene permisos para actualizar esta finca.');
            }

            // Propietario cannot change ownership
            if (isset($data['propietario_id']) && (int) $data['propietario_id'] !== $propietario->id) {
                throw new AuthorizationException('No puede cambiar el propietario de la finca.');
            }
        }

        return DB::transaction(function () use ($finca, $data) {
            $fincaUpdate = [];
            if (array_key_exists('nombre', $data)) $fincaUpdate['nombre'] = $data['nombre'];
            if (array_key_exists('explotacion_tipo', $data)) $fincaUpdate['explotacion_tipo'] = $data['explotacion_tipo'];
            if (array_key_exists('propietario_id', $data)) $fincaUpdate['propietario_id'] = $data['propietario_id'];

            if (!empty($fincaUpdate)) {
                $finca->update($fincaUpdate);
            }

            if (array_key_exists('terreno', $data) && is_array($data['terreno'])) {
                $terreno = $finca->terreno;
                if ($terreno) {
                    $terreno->update($data['terreno']);
                } else {
                    $terrenoData = $data['terreno'];
                    $terrenoData['finca_id'] = $finca->id;
                    Terreno::create($terrenoData);
                }
            }

            return $finca->fresh(['propietario.persona.users', 'terreno']);
        });
    }

    /**
     * Archive a finca (sets archived to true).
     *
     * @param int $id
     * @param User $user
     * @return void
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function archiveFinca(int $id, User $user): void
    {
        $finca = Finca::findOrFail($id);

        if (!$user->isAdmin()) {
            $propietario = $user->propietario;
            if (!$propietario || $finca->propietario_id !== $propietario->id) {
                throw new AuthorizationException('No tiene permisos para eliminar esta finca.');
            }
        }

        $finca->update(['archivado' => true]);
    }
}
