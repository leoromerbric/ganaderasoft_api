<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dosis extends Model
{
    use HasFactory;

    protected $table = 'dosis';
    protected $primaryKey = 'dosis_id';

    protected $fillable = [
        'dosis_vacuna_id',
        'dosis_casa_id',
        'dosis_objetivo_tipo',
        'dosis_objetivo_animal_id',
        'dosis_objetivo_rebano_id',
        'dosis_objetivo_filtros',
        'dosis_frecuencia',
        'dosis_costo',
        'dosis_costo_frasco',
        'dosis_fecha_uso_ini',
        'dosis_fecha_uso_fin',
        'dosis_observacion',
    ];

    protected $casts = [
        'dosis_objetivo_filtros' => 'array',
        'dosis_frecuencia'    => 'integer',
        'dosis_costo'         => 'float',
        'dosis_costo_frasco'  => 'float',
        'dosis_fecha_uso_ini' => 'date',
        'dosis_fecha_uso_fin' => 'date',
    ];

    public function vacuna()
    {
        return $this->belongsTo(Vacuna::class, 'dosis_vacuna_id', 'vacuna_id');
    }

    public function casaComercial()
    {
        return $this->belongsTo(CasaComercial::class, 'dosis_casa_id', 'casa_id');
    }

    public function animal()
    {
        return $this->belongsTo(Animal::class, 'dosis_objetivo_animal_id', 'id_Animal');
    }

    public function rebano()
    {
        return $this->belongsTo(Rebano::class, 'dosis_objetivo_rebano_id', 'id_Rebano');
    }

    public function historicoAplicaciones()
    {
        return $this->hasMany(HistoricoAplicacion::class, 'ha_dosis_id', 'dosis_id');
    }

    public function scopeForVacuna($query, $vacunaId)
    {
        return $query->where('dosis_vacuna_id', $vacunaId);
    }

    public function scopeVigentes($query)
    {
        return $query->where('dosis_fecha_uso_ini', '<=', now()->toDateString())
                     ->where(function ($q) {
                         $q->whereNull('dosis_fecha_uso_fin')
                           ->orWhere('dosis_fecha_uso_fin', '>=', now()->toDateString());
                     });
    }

    public function scopeByObjetivoTipo($query, $tipo)
    {
        return $query->where('dosis_objetivo_tipo', $tipo);
    }
}
