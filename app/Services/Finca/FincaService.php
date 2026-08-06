<?php

namespace App\Services\Finca;

use App\Models\Finca;
use App\Models\Terreno;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Services\BaseService;

class FincaService extends BaseService
{
    /**
     * Obtener lista de fincas paginada según permisos.
     *
     * @param array $filters
     * @param User $user
     * @return LengthAwarePaginator
     * @throws AuthorizationException
     */
    public function listFincas(array $filters, User $user)
    {
        if ($user->cannot('readAny', Finca::class)) {
            throw new AuthorizationException('Sin permisos para listar fincas.');
        }

        $query = Finca::with(['propietario.persona.users', 'terreno'])->active();

        $this->applyFincaFilter($query, $user, null, 'id');

        if (!empty($filters['nopaginate'])) {
            return $query->get();
        }

        return $query->paginate(15);
    }

    /**
     * Crear una nueva finca.
     *
     * @param array $data
     * @param User $user
     * @return Finca
     * @throws AuthorizationException
     */
    public function storeFinca(array $data, User $user): Finca
    {
        $propietarioId = (int) $data['propietario_id'];

        if ($user->cannot('create', [Finca::class, $propietarioId])) {
            throw new AuthorizationException('No tiene permisos para crear fincas para este propietario.');
        }

        return DB::transaction(function () use ($data, $user) {
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

            // Asignar automáticamente la finca al usuario que la está creando 
            // (a menos que sea administrador, para no saturar su lista)
            // Si el creador NO es un Admin y NO es el Propietario de esta finca, 
            // significa que es un trabajador. Solo en ese caso lo registramos en finca_user.
            if (!$user->isAdmin()) {
                $isTheOwner = $user->propietario && $user->propietario->id === (int) $data['propietario_id'];
                
                if (!$isTheOwner) {
                    $finca->users()->attach($user->id, [
                        'access_level' => 'operator',
                        'is_default' => false,
                        'status' => 'active'
                    ]);
                }
            }

            return $finca->load(['propietario.persona.users', 'terreno']);
        });
    }

    /**
     * Obtener una finca específica validando permisos.
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

        if ($user->cannot('read', $finca)) {
            throw new AuthorizationException('No tiene permisos para ver esta finca.');
        }

        return $finca;
    }

    /**
     * Actualizar los datos de una finca.
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

        $nuevoPropietarioId = isset($data['propietario_id']) ? (int) $data['propietario_id'] : null;

        if ($user->cannot('update', [$finca, $nuevoPropietarioId])) {
            throw new AuthorizationException('No tiene permisos para actualizar esta finca o cambiar su propietario.');
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
     * Archivar una finca (estado archivado = true).
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

        if ($user->cannot('delete', $finca)) {
            throw new AuthorizationException('No tiene permisos para eliminar esta finca.');
        }

        $finca->update(['archivado' => true]);
    }
}
