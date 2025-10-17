<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lactancia extends Model
{
    use HasFactory;

    protected $table = 'lactancia';
    protected $primaryKey = 'lactancia_id';

    protected $fillable = [
        'lactancia_fecha_inicio',
        'Lactancia_fecha_fin',
        'lactancia_secado',
        'lactancia_etapa_anid',
        'lactancia_etapa_etid',
    ];

    protected $casts = [
        'lactancia_fecha_inicio' => 'date',
        'Lactancia_fecha_fin' => 'date',
        'lactancia_secado' => 'date',
    ];

    /**
     * Get the etapa animal relationship.
     * Using whereRaw to handle composite keys properly.
     */
    public function etapaAnimal()
    {
        return $this->hasOne(EtapaAnimal::class)
                    ->whereRaw('etan_animal_id = lactancia_etapa_anid AND etan_etapa_id = lactancia_etapa_etid');
    }

    /**
     * Get the animal through the direct foreign key.
     */
    public function animal()
    {
        return $this->belongsTo(Animal::class, 'lactancia_etapa_anid', 'id_Animal');
    }

    /**
     * Get the leche records for this lactancia.
     */
    public function lecheRecords()
    {
        return $this->hasMany(Leche::class, 'leche_lactancia_id', 'lactancia_id');
    }

    /**
     * Scope a query to filter by active lactation (no end date).
     */
    public function scopeActive($query)
    {
        return $query->whereNull('Lactancia_fecha_fin');
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeByDateRange($query, $startDate, $endDate = null)
    {
        if ($endDate) {
            return $query->whereBetween('lactancia_fecha_inicio', [$startDate, $endDate]);
        }
        return $query->where('lactancia_fecha_inicio', '>=', $startDate);
    }

    /**
     * Scope a query to filter by animal.
     */
    public function scopeForAnimal($query, $animalId)
    {
        return $query->where('lactancia_etapa_anid', $animalId);
    }
}