<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServicioAnimal extends Model
{
    use HasFactory;

    protected $table = 'servicio_animal';
    protected $primaryKey = 'servicio_id';

    protected $fillable = [
        'servicio_id_Animal',
        'servicio_semen_id',
        'servicio_id_Tecnico',
        'servicio_tipo',
        'servicio_fecha',
        'servicio_observacion',
        'servicio_celo_id',
    ];

    protected $casts = [
        'servicio_fecha' => 'date',
    ];

    public function animal()
    {
        return $this->belongsTo(Animal::class, 'servicio_id_Animal', 'id_Animal');
    }

    public function semen()
    {
        return $this->belongsTo(SemenToro::class, 'servicio_semen_id', 'semen_id');
    }

    public function tecnico()
    {
        return $this->belongsTo(PersonalFinca::class, 'servicio_id_Tecnico', 'id_Tecnico');
    }

    public function registroCelo()
    {
        return $this->belongsTo(RegistroCelo::class, 'servicio_celo_id', 'celo_id');
    }

    public function scopeForAnimal($query, $animalId)
    {
        return $query->where('servicio_id_Animal', $animalId);
    }

    public function scopeByTipo($query, $tipo)
    {
        return $query->where('servicio_tipo', $tipo);
    }

    public function scopeByDateRange($query, $startDate, $endDate = null)
    {
        if ($endDate) {
            return $query->whereBetween('servicio_fecha', [$startDate, $endDate]);
        }
        return $query->where('servicio_fecha', '>=', $startDate);
    }
}
