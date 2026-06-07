<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegistroCelo extends Model
{
    use HasFactory;

    protected $table = 'registro_celo';
    protected $primaryKey = 'celo_id';
    public $timestamps = false;

    protected $fillable = [
        'celo_fecha',
        'celo_observacon',
        'celo_etapa_anid',
        'celo_etapa_etid',
    ];

    protected $casts = [
        'celo_fecha' => 'date',
    ];

    public function animal()
    {
        return $this->belongsTo(Animal::class, 'celo_etapa_anid', 'id_Animal');
    }

    public function etapa()
    {
        return $this->belongsTo(Etapa::class, 'celo_etapa_etid', 'etapa_id');
    }

    public function servicios()
    {
        return $this->hasMany(ServicioAnimal::class, 'servicio_celo_id', 'celo_id');
    }

    public function scopeForAnimal($query, $animalId)
    {
        return $query->where('celo_etapa_anid', $animalId);
    }

    public function scopeByDateRange($query, $startDate, $endDate = null)
    {
        if ($endDate) {
            return $query->whereBetween('celo_fecha', [$startDate, $endDate]);
        }
        return $query->where('celo_fecha', '>=', $startDate);
    }
}
