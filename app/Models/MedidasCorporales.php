<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedidasCorporales extends Model
{
    use HasFactory;

    protected $table = 'medidas_corporales';

    protected $primaryKey = 'id_Medida';

    protected $fillable = [
        'Altura_HC',
        'Altura_HG',
        'Perimetro_PT',
        'Perimetro_PCA',
        'Longitud_LC',
        'Longitud_LG',
        'Anchura_AG',
        'medida_etapa_anid',
        'medida_etapa_etid',
    ];

    protected $casts = [
        'Altura_HC' => 'float',
        'Altura_HG' => 'float',
        'Perimetro_PT' => 'float',
        'Perimetro_PCA' => 'float',
        'Longitud_LC' => 'float',
        'Longitud_LG' => 'float',
        'Anchura_AG' => 'float',
    ];

    /**
     * Get the etapa animal relationship.
     * Using whereColumn to handle composite keys properly.
     */
    public function etapaAnimal()
    {
        return $this->hasOne(EtapaAnimal::class, 'etan_animal_id', 'medida_etapa_anid')
            ->whereColumn('etan_etapa_id', 'medidas_corporales.medida_etapa_etid');
    }

    /**
     * Get the animal through the direct foreign key.
     */
    public function animal()
    {
        return $this->belongsTo(Animal::class, 'medida_etapa_anid', 'id_Animal');
    }

    /**
     * Scope a query to filter by animal.
     */
    public function scopeForAnimal($query, $animalId)
    {
        return $query->where('medida_etapa_anid', $animalId);
    }

    /**
     * Scope a query to filter by etapa.
     */
    public function scopeForEtapa($query, $etapaId)
    {
        return $query->where('medida_etapa_etid', $etapaId);
    }
}
