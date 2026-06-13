<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoricoAplicacion extends Model
{
    use HasFactory;

    protected $table = 'historico_aplicacion';
    protected $primaryKey = 'id_ha';
    protected $appends = ['ha_id'];

    protected $fillable = [
        'ha_vacuna_id',
        'ha_casa_id',
        'ha_dosis_id',
        'ha_animal_id',
        'ha_origen_tipo',
        'fecha_inyeccion',
        'observacion',
    ];

    protected $casts = [
        'fecha_inyeccion' => 'date',
    ];

    public function getHaIdAttribute(): int
    {
        return (int) $this->id_ha;
    }

    public function vacuna()
    {
        return $this->belongsTo(Vacuna::class, 'ha_vacuna_id', 'vacuna_id');
    }

    public function casaComercial()
    {
        return $this->belongsTo(CasaComercial::class, 'ha_casa_id', 'casa_id');
    }

    public function dosis()
    {
        return $this->belongsTo(Dosis::class, 'ha_dosis_id', 'dosis_id');
    }

    public function animal()
    {
        return $this->belongsTo(Animal::class, 'ha_animal_id', 'id_Animal');
    }

    public function scopeByVacuna($query, $vacunaId)
    {
        return $query->where('ha_vacuna_id', $vacunaId);
    }

    public function scopeByDateRange($query, $startDate, $endDate = null)
    {
        if ($endDate) {
            return $query->whereBetween('fecha_inyeccion', [$startDate, $endDate]);
        }
        return $query->where('fecha_inyeccion', '>=', $startDate);
    }

    public function scopeForAnimal($query, $animalId)
    {
        return $query->where('ha_animal_id', $animalId);
    }
}
