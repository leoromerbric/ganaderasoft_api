<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Palpacion extends Model
{
    use HasFactory;

    protected $table = 'palpacion';
    protected $primaryKey = 'palpacion_id';

    protected $fillable = [
        'id_Tecnico',
        'palpacion_tipo',
        'palpacion_fecha',
        'palpacion_etapa_anid',
        'palpacion_etapa_etid',
    ];

    protected $casts = [
        'palpacion_fecha' => 'date',
    ];

    public function animal()
    {
        return $this->belongsTo(Animal::class, 'palpacion_etapa_anid', 'id_Animal');
    }

    public function etapa()
    {
        return $this->belongsTo(Etapa::class, 'palpacion_etapa_etid', 'etapa_id');
    }

    public function tecnico()
    {
        return $this->belongsTo(PersonalFinca::class, 'id_Tecnico', 'id_Tecnico');
    }

    public function scopeForAnimal($query, $animalId)
    {
        return $query->where('palpacion_etapa_anid', $animalId);
    }

    public function scopeByTipo($query, $tipo)
    {
        return $query->where('palpacion_tipo', $tipo);
    }

    public function scopeByDateRange($query, $startDate, $endDate = null)
    {
        if ($endDate) {
            return $query->whereBetween('palpacion_fecha', [$startDate, $endDate]);
        }
        return $query->where('palpacion_fecha', '>=', $startDate);
    }
}
