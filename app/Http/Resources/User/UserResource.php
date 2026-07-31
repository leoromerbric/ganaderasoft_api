<?php

namespace App\Http\Resources\User;

use App\Http\Resources\Persona\PropietarioResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transforma el recurso en un arreglo.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (!$this->resource) {
            return null;
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at ? $this->email_verified_at->toIso8601String() : null,
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
            'status' => $this->status,
            'roles' => $this->whenLoaded('roles', fn() => $this->roles->pluck('code')),
            
            // Relación de propietario si las personas y su propietario están cargados
            'propietario' => $this->when($this->relationLoaded('personas'), function() {
                $persona = $this->personas->first();
                if (!$persona || !$persona->relationLoaded('propietario')) return null;
                return $persona->propietario ? new PropietarioResource($persona->propietario) : null;
            }),
            
            // El propietario sigue siendo requerido por los middlewares legacy (v1)
        ];
    }
}
