<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CambiosAnimal extends Model
{
    use HasFactory;

    protected $table = 'cambios_animal';

    protected $primaryKey = 'id_Cambio';

    protected $fillable = [
        'Fecha_Cambio',
        'Etapa_Cambio',
        'Peso',
        'Altura',
        'Comentario',
        'cambios_etapa_anid',
        'cambios_etapa_etid',
    ];

    protected $casts = [
        'Fecha_Cambio' => 'date',
        'Peso' => 'float',
        'Altura' => 'float',
    ];

    /**
     * Get the etapa animal relationship.
     * Using whereColumn to handle composite keys properly.
     */
    public function etapaAnimal()
    {
        return $this->hasOne(EtapaAnimal::class, 'etan_animal_id', 'cambios_etapa_anid')
            ->whereColumn('etan_etapa_id', 'cambios_animal.cambios_etapa_etid');
    }

    /**
     * Get the animal through the direct foreign key.
     */
    public function animal()
    {
        return $this->belongsTo(Animal::class, 'cambios_etapa_anid', 'id_Animal');
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeByDateRange($query, $startDate, $endDate = null)
    {
        if ($endDate) {
            return $query->whereBetween('Fecha_Cambio', [$startDate, $endDate]);
        }

        return $query->where('Fecha_Cambio', '>=', $startDate);
    }

    /**
     * Scope a query to filter by animal.
     */
    public function scopeForAnimal($query, $animalId)
    {
        return $query->where('cambios_etapa_anid', $animalId);
    }

    /**
     * Scope a query to filter by etapa.
     */
    public function scopeForEtapa($query, $etapaId)
    {
        return $query->where('cambios_etapa_etid', $etapaId);
    }

    /**
     * Scope a query to filter by etapa cambio.
     */
    public function scopeByEtapaCambio($query, $etapaCambio)
    {
        return $query->where('Etapa_Cambio', $etapaCambio);
    }
}
