<?php

namespace App\Http\Resources\Propietario;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropietarioResource extends JsonResource
{
    /**
     * Transform the resource into an array.
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
            'Nombre' => $this->persona ? $this->persona->nombre : '',
            'Apellido' => $this->persona ? $this->persona->apellido : '',
            'Telefono' => $this->persona ? $this->persona->telefono : '',
            'archivado' => $this->persona ? ($this->persona->status === 'inactivo') : false,
        ];
    }
}
