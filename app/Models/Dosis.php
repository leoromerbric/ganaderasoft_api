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
        'dosis_frecuencia',
        'dosis_costo',
        'dosis_costo_frasco',
        'dosis_fecha_uso_ini',
        'dosis_fecha_uso_fin',
        'dosis_etapa_animal_anid',
        'dosis_etapa_animal_etid',
    ];

    protected $casts = [
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

    // Note: dosis_etapa_animal_anid/etid columns are swapped in DB (known inconsistency).
    // Using dosis_etapa_animal_etid as the actual animal FK.
    public function animal()
    {
        return $this->belongsTo(Animal::class, 'dosis_etapa_animal_etid', 'id_Animal');
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
}
