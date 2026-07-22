<?php

namespace App\Http\Resources\Sanidad;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Animal\AnimalResource;

class VacunacionResource extends JsonResource
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
            return [];
        }

        return [
            'id'             => $this->id,
            'vacuna_id'      => $this->vacuna_id,
            'casa_comercial_id' => $this->casa_comercial_id,
            'rebano_id'      => $this->rebano_id,
            'modo_seleccion' => $this->modo_seleccion,
            'filtros'        => $this->filtros,
            'fecha'          => $this->fecha ? $this->fecha->format('Y-m-d') : null,
            'costo_dosis'    => (float) $this->costo_dosis,
            'total_animales' => (int) $this->total_animales,
            'monto_total'    => (float) $this->monto_total,
            'observacion'    => $this->observacion,
            'created_at'     => $this->created_at,
            'updated_at'     => $this->updated_at,
            
            // Relaciones
            'vacuna'         => $this->whenLoaded('vacuna'),
            'rebano'         => $this->whenLoaded('rebano'),
            'animales'       => $this->whenLoaded('animales', function () {
                // Mapear VacunacionAnimal a Animal
                return $this->animales->map(function ($va) {
                    return $va->animal; // Enviar solo los animales planos si es necesario, o la estructura completa. 
                    // El controller anterior mandaba animales.animal (así que mandaremos todo)
                });
            }),
            'animales_count' => $this->whenHas('animales_count'),
        ];
    }
}
