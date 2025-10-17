<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PesoCorporal extends Model
{
    use HasFactory;

    protected $table = 'peso_corporal';

    protected $primaryKey = 'id_Peso';

    protected $fillable = [
        'Fecha_Peso',
        'Peso',
        'Comentario',
        'peso_etapa_anid',
        'peso_etapa_etid',
    ];

    protected $casts = [
        'Fecha_Peso' => 'date',
        'Peso' => 'float',
    ];

    /**
     * Get the etapa animal relationship.
     * Using whereColumn to handle composite keys properly.
     */
    public function etapaAnimal()
    {
        return $this->hasOne(EtapaAnimal::class, 'etan_animal_id', 'peso_etapa_anid')
            ->whereColumn('etan_etapa_id', 'peso_corporal.peso_etapa_etid');
    }

    /**
     * Get the animal through the direct foreign key.
     */
    public function animal()
    {
        return $this->belongsTo(Animal::class, 'peso_etapa_anid', 'id_Animal');
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeByDateRange($query, $startDate, $endDate = null)
    {
        if ($endDate) {
            return $query->whereBetween('Fecha_Peso', [$startDate, $endDate]);
        }

        return $query->where('Fecha_Peso', '>=', $startDate);
    }

    /**
     * Scope a query to filter by animal.
     */
    public function scopeForAnimal($query, $animalId)
    {
        return $query->where('peso_etapa_anid', $animalId);
    }
}
