<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vacunacion extends Model
{
    use HasFactory;

    protected $table = 'vacunacion';
    protected $primaryKey = 'vacunacion_id';

    protected $fillable = [
        'vacunacion_vacuna_id',
        'vacunacion_casa_id',
        'vacunacion_rebano_id',
        'vacunacion_modo_seleccion',
        'vacunacion_filtros',
        'vacunacion_fecha',
        'vacunacion_costo_dosis',
        'vacunacion_total_animales',
        'vacunacion_monto_total',
        'vacunacion_observacion',
    ];

    protected $casts = [
        'vacunacion_filtros' => 'array',
        'vacunacion_fecha' => 'date',
        'vacunacion_costo_dosis' => 'float',
        'vacunacion_total_animales' => 'integer',
        'vacunacion_monto_total' => 'float',
    ];

    public function vacuna()
    {
        return $this->belongsTo(Vacuna::class, 'vacunacion_vacuna_id', 'vacuna_id');
    }

    public function casaComercial()
    {
        return $this->belongsTo(CasaComercial::class, 'vacunacion_casa_id', 'casa_id');
    }

    public function rebano()
    {
        return $this->belongsTo(Rebano::class, 'vacunacion_rebano_id', 'id_Rebano');
    }

    public function animales()
    {
        return $this->hasMany(VacunacionAnimal::class, 'va_vacunacion_id', 'vacunacion_id');
    }

    public function scopeForVacuna($query, $vacunaId)
    {
        return $query->where('vacunacion_vacuna_id', $vacunaId);
    }

    public function scopeForRebano($query, $rebanoId)
    {
        return $query->where('vacunacion_rebano_id', $rebanoId);
    }
}
