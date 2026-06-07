<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Diagnostico extends Model
{
    use HasFactory;

    protected $table = 'diagnostico';
    protected $primaryKey = 'diagnostico_id';

    protected $fillable = [
        'diagnostico_descripcion',
        'diagnostico_tipo',
        'diagnostico_fecha',
        'fk_etapa_animal_anid',
        'fk_etapa_animal_etid',
    ];

    protected $casts = [
        'diagnostico_fecha' => 'date',
    ];

    public function animal()
    {
        return $this->belongsTo(Animal::class, 'fk_etapa_animal_anid', 'id_Animal');
    }

    public function etapa()
    {
        return $this->belongsTo(Etapa::class, 'fk_etapa_animal_etid', 'etapa_id');
    }

    public function tratamientos()
    {
        return $this->hasMany(Tratamiento::class, 'tratamiento_diagnostico_id', 'diagnostico_id');
    }

    public function scopeForAnimal($query, $animalId)
    {
        return $query->where('fk_etapa_animal_anid', $animalId);
    }

    public function scopeByTipo($query, $tipo)
    {
        return $query->where('diagnostico_tipo', $tipo);
    }

    public function scopeByDateRange($query, $startDate, $endDate = null)
    {
        if ($endDate) {
            return $query->whereBetween('diagnostico_fecha', [$startDate, $endDate]);
        }
        return $query->where('diagnostico_fecha', '>=', $startDate);
    }
}
