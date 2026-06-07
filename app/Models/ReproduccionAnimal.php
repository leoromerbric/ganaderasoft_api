<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReproduccionAnimal extends Model
{
    use HasFactory;

    protected $table = 'reproduccion_animal';
    protected $primaryKey = 'repro_id';

    protected $fillable = [
        'repro_fecha_reproduccion',
        'repro_tipo_reproduccion',
        'repro_observacion',
        'repro_etapa_anid',
        'repro_etapa_etid',
    ];

    protected $casts = [
        'repro_fecha_reproduccion' => 'date',
    ];

    public function animal()
    {
        return $this->belongsTo(Animal::class, 'repro_etapa_anid', 'id_Animal');
    }

    public function etapa()
    {
        return $this->belongsTo(Etapa::class, 'repro_etapa_etid', 'etapa_id');
    }

    public function scopeForAnimal($query, $animalId)
    {
        return $query->where('repro_etapa_anid', $animalId);
    }

    public function scopeByTipo($query, $tipo)
    {
        return $query->where('repro_tipo_reproduccion', $tipo);
    }

    public function scopeByDateRange($query, $startDate, $endDate = null)
    {
        if ($endDate) {
            return $query->whereBetween('repro_fecha_reproduccion', [$startDate, $endDate]);
        }
        return $query->where('repro_fecha_reproduccion', '>=', $startDate);
    }
}
