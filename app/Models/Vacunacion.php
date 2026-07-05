<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vacunacion extends Model
{
    use HasFactory;

    protected $fillable = [
        'vacuna_id',
        'casa_comercial_id',
        'rebano_id',
        'modo_seleccion',
        'filtros',
        'fecha',
        'costo_dosis',
        'total_animales',
        'monto_total',
        'observacion',
    ];

    protected $casts = [
        'filtros' => 'array',
        'fecha' => 'date',
        'costo_dosis' => 'float',
        'total_animales' => 'integer',
        'monto_total' => 'float',
    ];

    /**
     * Obtener vacuna asociado/a.
     */
    public function vacuna()
    {
        return $this->belongsTo(Vacuna::class, 'vacuna_id', 'id');
    }

    /**
     * Obtener casa comercial asociado/a.
     */
    public function casaComercial()
    {
        return $this->belongsTo(CasaComercial::class, 'casa_comercial_id', 'id');
    }

    /**
     * Obtener rebano asociado/a.
     */
    public function rebano()
    {
        return $this->belongsTo(Rebano::class, 'rebano_id', 'id');
    }

    /**
     * Obtener animales asociado/a.
     */
    public function animales()
    {
        return $this->hasMany(VacunacionAnimal::class, 'vacunacion_id', 'id');
    }

    /**
     * Filtro para buscar por for vacuna.
     */
    public function scopeForVacuna($query, $vacunaId)
    {
        return $query->where('vacuna_id', $vacunaId);
    }

    /**
     * Filtro para buscar por for rebano.
     */
    public function scopeForRebano($query, $rebanoId)
    {
        return $query->where('rebano_id', $rebanoId);
    }
}
