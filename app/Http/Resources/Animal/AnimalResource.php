<?php

namespace App\Http\Resources\Animal;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnimalResource extends JsonResource
{
    /**
     * Transforma el recurso a un array compatible con la estructura antigua esperada por el front-end.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_Animal' => $this->id,
            'id_Rebano' => $this->rebano_id,
            'Nombre' => $this->nombre,
            'codigo_animal' => $this->codigo_animal,
            'Sexo' => $this->sexo,
            'fecha_nacimiento' => $this->fecha_nacimiento ? $this->fecha_nacimiento->toIso8601String() : null,
            'Procedencia' => $this->procedencia,
            'fk_composicion_raza' => $this->composicion_raza_id,
            'archivado' => (bool) $this->archivado,
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toIso8601String() : null,
            
            // Relación de Rebaño, Finca y Propietario anidadas según el formato anterior
            'rebano' => $this->whenLoaded('rebano', function() {
                if (!$this->rebano) return null;
                return [
                    'id_Rebano' => $this->rebano->id,
                    'id_Finca' => $this->rebano->finca_id,
                    'Nombre' => $this->rebano->nombre,
                    'archivado' => (bool) $this->rebano->archivado,
                    'created_at' => $this->rebano->created_at ? $this->rebano->created_at->toIso8601String() : null,
                    'updated_at' => $this->rebano->updated_at ? $this->rebano->updated_at->toIso8601String() : null,
                    
                    'finca' => $this->when($this->rebano->relationLoaded('finca'), function() {
                        if (!$this->rebano->finca) return null;
                        return [
                            'id_Finca' => $this->rebano->finca->id,
                            'id_Propietario' => $this->rebano->finca->propietario_id,
                            'Nombre' => $this->rebano->finca->nombre,
                            'explotacion_tipo' => $this->rebano->finca->explotacion_tipo,
                            'archivado' => (bool) $this->rebano->finca->archivado,
                            'created_at' => $this->rebano->finca->created_at ? $this->rebano->finca->created_at->toIso8601String() : null,
                            'updated_at' => $this->rebano->finca->updated_at ? $this->rebano->finca->updated_at->toIso8601String() : null,
                            
                            'propietario' => $this->when($this->rebano->finca->relationLoaded('propietario'), function() {
                                if (!$this->rebano->finca->propietario) return null;
                                $prop = $this->rebano->finca->propietario;
                                
                                $propData = [
                                    'id_Propietario' => $prop->id,
                                ];
                                
                                // Solo incluimos persona si fue cargada en la consulta para evitar queries N+1
                                if ($prop->relationLoaded('persona') && $prop->persona) {
                                    $propData['persona'] = [
                                        'id' => $prop->persona->id,
                                        'cedula' => $prop->persona->cedula,
                                        'nombre' => $prop->persona->nombre,
                                        'apellido' => $prop->persona->apellido,
                                        'telefono' => $prop->persona->telefono,
                                        'correo' => $prop->persona->correo,
                                        'status' => $prop->persona->status,
                                        'created_at' => $prop->persona->created_at ? $prop->persona->created_at->toIso8601String() : null,
                                        'updated_at' => $prop->persona->updated_at ? $prop->persona->updated_at->toIso8601String() : null,
                                    ];
                                }
                                
                                return $propData;
                            }),
                        ];
                    }),
                ];
            }),
            
            // Composición de Raza formateada
            'composicion_raza' => $this->whenLoaded('composicionRaza', function() {
                if (!$this->composicionRaza) return null;
                return [
                    'id_Composicion' => $this->composicionRaza->id,
                    'Nombre' => $this->composicionRaza->nombre,
                    'Siglas' => $this->composicionRaza->siglas,
                    'Pelaje' => $this->composicionRaza->pelaje,
                    'Proposito' => $this->composicionRaza->proposito,
                    'Tipo_Raza' => $this->composicionRaza->tipo_raza,
                    'Origen' => $this->composicionRaza->origen,
                    'Caracteristica_Especial' => $this->composicionRaza->caracteristica_especial,
                    'Proporcion_Raza' => $this->composicionRaza->proporcion_raza,
                    'created_at' => $this->composicionRaza->created_at ? $this->composicionRaza->created_at->toIso8601String() : null,
                    'updated_at' => $this->composicionRaza->updated_at ? $this->composicionRaza->updated_at->toIso8601String() : null,
                    'fk_id_Finca' => $this->composicionRaza->finca_id,
                    'fk_tipo_animal_id' => $this->composicionRaza->tipo_animal_id,
                ];
            }),

            // Relaciones condicionales que se omiten por completo si no fueron cargadas en la consulta
            'estado_actual' => $this->whenLoaded('estadoActual', fn() => new EstadoAnimalResource($this->estadoActual)),
            'etapa_actual' => $this->whenLoaded('etapaActual', fn() => new EtapaAnimalResource($this->etapaActual)),
            'estados' => $this->whenLoaded('estados', fn() => EstadoAnimalResource::collection($this->estados)),
            'etapa_animales' => $this->whenLoaded('etapaAnimales', fn() => EtapaAnimalResource::collection($this->etapaAnimales)),
        ];
    }
}
