<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Finca\FincaResource;

class UserFincaResource extends JsonResource
{
    /**
     * Transforma el recurso a un arreglo.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (!$this->resource) {
            return null;
        }

        // Devolvemos la estructura de la Finca y le adjuntamos la información del acceso
        return array_merge((new FincaResource($this->resource))->toArray($request), [
            'access_level' => $this->whenPivotLoaded('finca_user', function () {
                return $this->pivot->access_level;
            }),
            'is_default' => $this->whenPivotLoaded('finca_user', function () {
                return (bool) $this->pivot->is_default;
            }),
            'status' => $this->whenPivotLoaded('finca_user', function () {
                return $this->pivot->status;
            }),
        ]);
    }
}
