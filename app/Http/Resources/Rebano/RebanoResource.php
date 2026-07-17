<?php

namespace App\Http\Resources\Rebano;

use App\Http\Resources\Finca\FincaResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RebanoResource extends JsonResource
{
    /**
     * Transforma el recurso de Rebaño a un array limpio estándar V2 (snake_case).
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
            'nombre' => $this->nombre,
            'archivado' => (bool) $this->archivado,
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toIso8601String() : null,
            
            // Omitir finca_id si la relación finca está cargada
            'finca_id' => $this->when(!$this->relationLoaded('finca'), $this->finca_id),
            
            'finca' => new FincaResource($this->whenLoaded('finca')),
        ];
    }
}
