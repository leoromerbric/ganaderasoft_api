<?php

namespace App\Http\Resources\Reportes;

use Illuminate\Http\Resources\Json\JsonResource;

class EstadisticasFincasResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        // $this->resource is the array returned by the service
        return [
            'success' => true,
            'message' => 'Estadísticas de fincas',
            'data' => [
                'resumen' => $this->resource['resumen'] ?? [],
                'animales_por_sexo' => $this->resource['animales_por_sexo'] ?? [],
                'personal_por_tipo' => $this->resource['personal_por_tipo'] ?? [],
                'fincas' => $this->resource['fincas'] ?? [],
                'rebanos' => $this->resource['rebanos'] ?? [],
            ],
        ];
    }
}
