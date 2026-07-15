<?php

namespace App\Http\Resources\Animal;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EstadoAnimalResource extends JsonResource
{
    /**
     * Transforma el recurso a un array compatible con el formato antiguo de la base de datos para el front-end.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'esan_id' => $this->id,
            'esan_fecha_ini' => $this->fecha_ini ? $this->fecha_ini->toDateString() : null,
            'esan_fecha_fin' => $this->fecha_fin ? $this->fecha_fin->toDateString() : null,
            'esan_fk_estado_id' => $this->estado_salud_id,
            'esan_fk_id_animal' => $this->animal_id,
            
            // Relación del estado de salud con claves antiguas (se omite si no está cargada)
            'estado_salud' => $this->whenLoaded('estadoSalud', function() {
                if (!$this->estadoSalud) return null;
                return [
                    'estado_id' => $this->estadoSalud->id,
                    'estado_nombre' => $this->estadoSalud->nombre,
                ];
            }),
            'animal' => $this->whenLoaded('animal', fn() => new AnimalResource($this->animal)),
        ];
    }
}
