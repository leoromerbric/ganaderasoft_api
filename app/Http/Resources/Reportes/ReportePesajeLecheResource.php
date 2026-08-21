<?php

namespace App\Http\Resources\Reportes;

use Illuminate\Http\Resources\Json\JsonResource;

class ReportePesajeLecheResource extends JsonResource
{
    /**
     * Transforma el recurso a un arreglo para la respuesta JSON.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'kpis'              => $this->resource['kpis'] ?? [],
            'items'             => $this->resource['items'] ?? [],
            'filtros_aplicados' => $this->resource['filtros_aplicados'] ?? [],
        ];
    }
}
