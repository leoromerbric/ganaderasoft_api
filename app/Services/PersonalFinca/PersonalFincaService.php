<?php

namespace App\Services\PersonalFinca;

use App\Models\PersonalFinca;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class PersonalFincaService
{
    public function listPersonal(array $filters, User $user)
    {
        $query = PersonalFinca::with(['finca', 'persona', 'tipoTrabajador']);

        if (!$user->isAdmin()) {
            if (!$user->isPropietario()) {
                throw new AuthorizationException('No tiene permisos para ver esta informacion');
            }
            $fincaIds = $user->propietario->fincas()->pluck('fincas.id');
            $query->whereIn('finca_id', $fincaIds);
        }

        if (isset($filters['finca_id'])) {
            $query->forFinca($filters['finca_id']);
        }

        if (isset($filters['tipo_trabajador_id'])) {
            $query->byTipoTrabajador($filters['tipo_trabajador_id']);
        }

        if (isset($filters['nombre'])) {
            $query->byName($filters['nombre']);
        }

        return $query->paginate(15);
    }

    public function storePersonal(array $data, User $user)
    {
        if (!$user->isAdmin()) {
            if (!$user->isPropietario()) {
                throw new AuthorizationException('No tiene permisos para agregar personal');
            }
            $fincaIds = $user->propietario->fincas()->pluck('fincas.id');
            if (!$fincaIds->contains($data['finca_id'])) {
                throw new AuthorizationException('No tiene permisos para agregar personal a esta finca');
            }
        }

        return DB::transaction(function () use ($data) {
            $persona = Persona::where('cedula', $data['cedula'])->first();

            $personaData = [
                'nombre' => $data['nombre'],
                'apellido' => $data['apellido'],
                'telefono' => $data['telefono'],
                'correo' => $data['correo'],
            ];

            if ($persona) {
                $persona->update($personaData);
            } else {
                $personaData['cedula'] = $data['cedula'];
                $personaData['status'] = true;
                $persona = Persona::create($personaData);
            }

            $personalFinca = PersonalFinca::create([
                'finca_id' => $data['finca_id'],
                'persona_id' => $persona->id,
                'tipo_trabajador_id' => $data['tipo_trabajador_id'],
                'status' => $data['status'] ?? true,
                'fecha_ingreso' => $data['fecha_ingreso'] ?? now()->toDateString(),
            ]);

            return $personalFinca->load(['finca', 'persona', 'tipoTrabajador']);
        });
    }

    public function getPersonal(int $id, User $user)
    {
        $personal = PersonalFinca::with(['finca', 'persona', 'tipoTrabajador'])->findOrFail($id);

        if (!$user->isAdmin()) {
            if (!$user->isPropietario()) {
                throw new AuthorizationException('No tiene permisos para ver esta informacion');
            }
            $fincaIds = $user->propietario->fincas()->pluck('fincas.id');
            if (!$fincaIds->contains($personal->finca_id)) {
                throw new AuthorizationException('No tiene permisos para ver este personal');
            }
        }

        return $personal;
    }

    public function updatePersonal(int $id, array $data, User $user)
    {
        $personal = PersonalFinca::findOrFail($id);

        if (!$user->isAdmin()) {
            if (!$user->isPropietario()) {
                throw new AuthorizationException('No tiene permisos para editar personal');
            }
            $fincaIds = $user->propietario->fincas()->pluck('fincas.id');
            if (!$fincaIds->contains($personal->finca_id)) {
                throw new AuthorizationException('No tiene permisos para editar este personal');
            }
            if (isset($data['finca_id']) && !$fincaIds->contains($data['finca_id'])) {
                throw new AuthorizationException('No tiene permisos para asignar personal a la nueva finca');
            }
        }

        return DB::transaction(function () use ($personal, $data) {
            if (isset($data['finca_id'])) $personal->finca_id = $data['finca_id'];
            if (isset($data['tipo_trabajador_id'])) $personal->tipo_trabajador_id = $data['tipo_trabajador_id'];
            if (isset($data['status'])) $personal->status = $data['status'];
            if (isset($data['fecha_ingreso'])) $personal->fecha_ingreso = $data['fecha_ingreso'];
            
            $personal->save();

            if (isset($data['nombre']) || isset($data['apellido']) || isset($data['telefono']) || isset($data['correo'])) {
                $persona = $personal->persona;
                if ($persona) {
                    $personaData = [];
                    if (isset($data['nombre'])) $personaData['nombre'] = $data['nombre'];
                    if (isset($data['apellido'])) $personaData['apellido'] = $data['apellido'];
                    if (isset($data['telefono'])) $personaData['telefono'] = $data['telefono'];
                    if (isset($data['correo'])) $personaData['correo'] = $data['correo'];
                    // Updating cedula is allowed if passed
                    if (isset($data['cedula'])) $personaData['cedula'] = $data['cedula'];
                    
                    $persona->update($personaData);
                }
            }

            return $personal->fresh(['finca', 'persona', 'tipoTrabajador']);
        });
    }

    public function deletePersonal(int $id, User $user)
    {
        $personal = PersonalFinca::findOrFail($id);

        if (!$user->isAdmin()) {
            if (!$user->isPropietario()) {
                throw new AuthorizationException('No tiene permisos para eliminar personal');
            }
            $fincaIds = $user->propietario->fincas()->pluck('fincas.id');
            if (!$fincaIds->contains($personal->finca_id)) {
                throw new AuthorizationException('No tiene permisos para eliminar este personal');
            }
        }

        $personal->delete();
        
        return true;
    }
}
