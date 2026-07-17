<?php

namespace App\Http\Resources\Animal;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EstadoAnimalResource extends JsonResource
{
    /**
     * Transforma el recurso a un array limpio estándar V2 (snake_case).
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (!$this->resource) {
            return null;
        }

        return [
            'id' => $this->id,
            'fecha_ini' => $this->fecha_ini ? $this->fecha_ini->toDateString() : null,
            'fecha_fin' => $this->fecha_fin ? $this->fecha_fin->toDateString() : null,
            'estado_salud_id' => $this->when(!$this->relationLoaded('estadoSalud'), $this->estado_salud_id),
            'animal_id' => $this->when(!$this->relationLoaded('animal'), $this->animal_id),
            
            'estado_salud' => $this->whenLoaded('estadoSalud'),
            'animal' => $this->whenLoaded('animal', fn() => new AnimalResource($this->animal)),
        ];
    }
}
