<?php

namespace App\Services\Persona;

use App\Models\Propietario;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class PropietarioService
{
    /**
     * List all propietarios with pagination based on user permissions.
     *
     * @param array $filters
     * @param User $user
     * @return LengthAwarePaginator
     * @throws AuthorizationException
     */
    public function listPropietarios(array $filters, User $user): LengthAwarePaginator
    {
        $query = Propietario::with(['persona.users', 'fincas'])->active();

        if ($user->isAdmin()) {
            return $query->paginate(15);
        }

        $propietario = $user->propietario;
        if (!$propietario) {
            throw new AuthorizationException('El usuario no está registrado como propietario.');
        }

        return $query->where('id', $propietario->id)->paginate(15);
    }

    /**
     * Store a newly created propietario.
     *
     * @param array $data
     * @param User $user
     * @return Propietario
     * @throws AuthorizationException
     * @throws ConflictHttpException
     */
    public function storePropietario(array $data, User $user): Propietario
    {
        $targetUserId = (int) $data['user_id'];

        // Access control: Only admin can create propietario profiles for other users
        if (!$user->isAdmin() && $targetUserId !== $user->id) {
            throw new AuthorizationException('No tiene permisos para crear propietario para otro usuario.');
        }

        $targetUser = User::findOrFail($targetUserId);

        // Check if propietario profile already exists for the user
        if ($targetUser->propietario) {
            throw new ConflictHttpException('Ya existe un propietario para este usuario.');
        }

        return DB::transaction(function () use ($data, $targetUser) {
            // Create the Persona physical entity
            $persona = Persona::create([
                'cedula' => $data['cedula'],
                'nombre' => $data['nombre'],
                'apellido' => $data['apellido'] ?? null,
                'telefono' => $data['telefono'] ?? null,
                'correo' => $data['correo'] ?? $targetUser->email,
                'status' => 'activo'
            ]);

            // Associate the Persona with the User
            $targetUser->personas()->attach($persona->id);

            // Create the Propietario role profile
            $propietario = Propietario::create([
                'persona_id' => $persona->id
            ]);

            return $propietario->load(['persona.users', 'fincas']);
        });
    }

    /**
     * Get a specific propietario detailing permissions.
     *
     * @param int $id
     * @param User $user
     * @return Propietario
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function getPropietario(int $id, User $user): Propietario
    {
        $propietario = Propietario::with(['persona.users', 'fincas'])->findOrFail($id);

        // Access control: Admin can see all, Propietario only their own profile
        if (!$user->isAdmin()) {
            $propietarioUser = $propietario->persona->users->first();
            if (!$propietarioUser || $user->id !== $propietarioUser->id) {
                throw new AuthorizationException('No tiene permisos para ver este propietario.');
            }
        }

        return $propietario;
    }

    /**
     * Update the details of a propietario's persona.
     *
     * @param int $id
     * @param array $data
     * @param User $user
     * @return Propietario
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function updatePropietario(int $id, array $data, User $user): Propietario
    {
        $propietario = Propietario::with(['persona.users', 'fincas'])->findOrFail($id);

        // Access control: Admin can update all, Propietario only their own
        if (!$user->isAdmin()) {
            $propietarioUser = $propietario->persona->users->first();
            if (!$propietarioUser || $user->id !== $propietarioUser->id) {
                throw new AuthorizationException('No tiene permisos para actualizar este propietario.');
            }
        }

        // Selectively update persona attributes
        $personaData = [];
        if (array_key_exists('cedula', $data)) $personaData['cedula'] = $data['cedula'];
        if (array_key_exists('nombre', $data)) $personaData['nombre'] = $data['nombre'];
        if (array_key_exists('apellido', $data)) $personaData['apellido'] = $data['apellido'];
        if (array_key_exists('telefono', $data)) $personaData['telefono'] = $data['telefono'];
        if (array_key_exists('correo', $data)) $personaData['correo'] = $data['correo'];

        if (!empty($personaData)) {
            $propietario->persona->update($personaData);
        }

        return $propietario->fresh(['persona.users', 'fincas']);
    }

    /**
     * Soft-delete/Archive a propietario (sets associated persona status to 'inactivo').
     *
     * @param int $id
     * @param User $user
     * @return void
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function archivePropietario(int $id, User $user): void
    {
        // Only admin can archive/delete propietarios
        if (!$user->isAdmin()) {
            throw new AuthorizationException('No tiene permisos para eliminar propietarios.');
        }

        $propietario = Propietario::findOrFail($id);
        $propietario->persona->update(['status' => 'inactivo']);
    }
}
