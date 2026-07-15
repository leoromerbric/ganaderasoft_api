<?php

namespace App\Http\Resources\Animal;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EtapaAnimalResource extends JsonResource
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
            'etan_id' => $this->id,
            'etan_fecha_ini' => $this->fecha_ini ? $this->fecha_ini->toDateString() : null,
            'etan_fecha_fin' => $this->fecha_fin ? $this->fecha_fin->toDateString() : null,
            'etan_animal_id' => $this->animal_id,
            'etan_etapa_id' => $this->etapa_id,

            // Relación de etapa con claves antiguas (se omite si no está cargada)
            'etapa' => $this->whenLoaded('etapa', function() {
                if (!$this->etapa) return null;
                return [
                    'etapa_id' => $this->etapa->id,
                    'etapa_nombre' => $this->etapa->nombre,
                    'etapa_edad_ini' => $this->etapa->edad_ini,
                    'etapa_edad_fin' => $this->etapa->edad_fin,
                    'etapa_sexo' => $this->etapa->sexo,
                    'etapa_fk_tipo_animal_id' => $this->etapa->tipo_animal_id,
                ];
            }),
            'animal' => $this->whenLoaded('animal', fn() => new AnimalResource($this->animal)),
        ];
    }
}
