<?php

namespace App\Services\User;

use App\Models\User;
use App\Models\Persona;
use App\Models\Role;
use App\Models\Propietario;
use App\Models\Administrador;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Access\AuthorizationException;

class UserService
{
    /**
     * Listar usuarios paginados con filtros
     */
    public function listUsers(array $filters, User $user)
    {
        if ($user->cannot('readAny', User::class)) {
            throw new AuthorizationException('No tienes permisos para ver usuarios.');
        }

        $query = User::with(['personas', 'roles', 'fincas']);

        if (!empty($filters['name'])) {
            $query->where('name', 'like', '%' . $filters['name'] . '%');
        }
        if (!empty($filters['email'])) {
            $query->where('email', 'like', '%' . $filters['email'] . '%');
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Si mandan nopaginate (como 'true' o 1), retornamos la colección completa
        if (isset($filters['nopaginate']) && filter_var($filters['nopaginate'], FILTER_VALIDATE_BOOLEAN)) {
            return $query->get();
        }

        return $query->paginate(15);
    }

    /**
     * Crear un nuevo usuario junto con su persona y rol
     */
    public function storeUser(array $data, User $adminUser)
    {
        if ($adminUser->cannot('create', User::class)) {
            throw new AuthorizationException('No tienes permisos para crear usuarios.');
        }

        $roleCodes = $data['roles'];
        $roles = Role::whereIn('code', $roleCodes)->get();
        if ($roles->count() !== count($roleCodes)) {
            throw new \Exception("Algunos roles especificados no existen.");
        }

        return DB::transaction(function () use ($data, $roles) {
            // Manejar Persona
            $persona = Persona::where('correo', $data['correo'])
                ->orWhere('cedula', $data['cedula'])
                ->first();

            if (!$persona) {
                $persona = Persona::create([
                    'cedula' => $data['cedula'],
                    'nombre' => $data['nombre'],
                    'apellido' => $data['apellido'],
                    'telefono' => $data['telefono'] ?? null,
                    'correo' => $data['correo']
                ]);
            }

            if ($persona->users()->exists()) {
                throw new \Exception('Esta persona ya tiene una cuenta de usuario vinculada.');
            }

            // Crear el usuario con el mismo correo que la persona
            $user = User::create([
                'name' => $data['nombre'] . ' ' . $data['apellido'],
                'email' => $data['correo'],
                'password' => Hash::make($data['password']),
                'status' => $data['status'] ?? 'active',
            ]);

            // Vincular
            $user->personas()->attach($persona->id);
            $user->roles()->sync($roles->pluck('id')->toArray());

            // Entidades asociadas al rol
            foreach ($roles as $role) {
                if ($role->code === 'propietario') {
                    Propietario::firstOrCreate(['persona_id' => $persona->id]);
                } elseif (in_array($role->code, ['global_admin', 'admin'])) {
                    Administrador::firstOrCreate(['persona_id' => $persona->id]);
                }
            }

            return $user->load('personas', 'roles');
        });
    }

    /**
     * Obtener un usuario por ID
     */
    public function getUser($id, User $adminUser)
    {
        $user = User::with(['personas', 'roles', 'fincas'])->findOrFail($id);

        if ($adminUser->cannot('read', $user)) {
            throw new AuthorizationException('No tienes permisos para ver este usuario.');
        }

        return $user;
    }

    /**
     * Actualizar datos base del usuario y de su persona
     */
    public function updateUser($id, array $data, User $adminUser)
    {
        $user = User::findOrFail($id);

        if ($adminUser->cannot('update', $user)) {
            throw new AuthorizationException('No tienes permisos para editar usuarios.');
        }

        return DB::transaction(function () use ($user, $data) {
            // Si cambian la contraseña
            if (!empty($data['password'])) {
                $user->password = Hash::make($data['password']);
            }
            if (isset($data['status'])) {
                $user->status = $data['status'];
                if ($data['status'] === 'suspended') {
                    $user->tokens()->delete();
                }
            }

            $persona = $user->personas()->first();

            // Si hay datos de la persona o correo que cambian
            if ($persona) {
                if (isset($data['nombre'])) $persona->nombre = $data['nombre'];
                if (isset($data['apellido'])) $persona->apellido = $data['apellido'];
                if (isset($data['cedula'])) $persona->cedula = $data['cedula'];
                if (isset($data['telefono'])) $persona->telefono = $data['telefono'];
                
                // Si cambia el correo, debe cambiar en ambos modelos
                if (isset($data['correo'])) {
                    $persona->correo = $data['correo'];
                    $user->email = $data['correo'];
                }
                
                $persona->save();
                
                // Si cambiaron nombre/apellido, actualizamos el 'name' del user
                if (isset($data['nombre']) || isset($data['apellido'])) {
                    $user->name = $persona->nombre . ' ' . $persona->apellido;
                }
            }

            $user->save();

            // Manejar cambio de roles si se proveen
            if (isset($data['roles'])) {
                $roleCodes = $data['roles'];
                $roles = Role::whereIn('code', $roleCodes)->get();
                if ($roles->count() === count($roleCodes)) {
                    $user->roles()->sync($roles->pluck('id')->toArray());

                    if ($persona) {
                        $newRoleCodes = $roles->pluck('code')->toArray();

                        // Crear o mantener los que vienen en el array
                        if (in_array('propietario', $newRoleCodes)) {
                            Propietario::firstOrCreate(['persona_id' => $persona->id]);
                        } else {
                            // Eliminar si ya no es propietario
                            Propietario::where('persona_id', $persona->id)->delete();
                        }

                        if (in_array('global_admin', $newRoleCodes) || in_array('admin', $newRoleCodes)) {
                            Administrador::firstOrCreate(['persona_id' => $persona->id]);
                        } else {
                            // Eliminar si ya no es administrador
                            Administrador::where('persona_id', $persona->id)->delete();
                        }
                    }
                }
            }

            return $user->load('personas', 'roles');
        });
    }

    /**
     * Eliminar usuario
     */
    public function deleteUser($id, User $adminUser)
    {
        $user = User::findOrFail($id);

        if ($adminUser->cannot('delete', $user)) {
            throw new AuthorizationException('No tienes permisos para eliminar usuarios.');
        }

        // Aquí podríamos validar si es el último superadmin antes de borrar
        if ($user->hasRole('global_admin') && User::whereHas('roles', function($q) {
            $q->where('code', 'global_admin');
        })->count() <= 1) {
            throw new \Exception('No puedes eliminar al último administrador global.');
        }

        return DB::transaction(function () use ($user) {
            // Eliminar dependencias suaves o forzadas según el caso
            $user->personas()->detach();
            $user->roles()->detach();
            $user->fincas()->detach();
            $user->delete();
            return true;
        });
    }
    /**
     * Desactivar (borrado lógico) un usuario
     */
    public function disableUser($id, User $adminUser)
    {
        $user = User::findOrFail($id);

        if ($adminUser->cannot('update', $user)) {
            throw new AuthorizationException('No tienes permisos para desactivar usuarios.');
        }

        if ($user->hasRole('global_admin') && User::whereHas('roles', function($q) {
            $q->where('code', 'global_admin');
        })->where('status', 'active')->count() <= 1) {
            throw new \Exception('No puedes desactivar al último administrador global activo.');
        }

        $user->update(['status' => 'suspended']);
        
        // Revocar todos los tokens de sesión actuales para expulsarlo de la app inmediatamente
        $user->tokens()->delete();
        
        return $user;
    }

    /**
     * Activar (restaurar lógicamente) un usuario
     */
    public function enableUser($id, User $adminUser)
    {
        $user = User::findOrFail($id);

        if ($adminUser->cannot('update', $user)) {
            throw new AuthorizationException('No tienes permisos para activar usuarios.');
        }

        $user->update(['status' => 'active']);
        return $user;
    }
}
