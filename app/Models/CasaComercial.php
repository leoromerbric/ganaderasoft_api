<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CasaComercial extends Model
{
    use HasFactory;

    protected $table = 'casa_comercial';
    protected $primaryKey = 'casa_id';

    protected $fillable = [
        'laboratorio',
        'marca_comercial',
        'activa',
    ];

    protected $casts = [
        'activa' => 'boolean',
    ];

    public function vacunas()
    {
        return $this->belongsToMany(
            Vacuna::class,
            'vacuna_casa',
            'vc_casa_id',
            'vc_vacuna_id'
        )->withPivot('dosis_cantidad');
    }

    public function dosis()
    {
        return $this->hasMany(Dosis::class, 'dosis_casa_id', 'casa_id');
    }

    public function scopeByLaboratorio($query, $laboratorio)
    {
        return $query->where('laboratorio', 'like', "%{$laboratorio}%");
    }

    public function scopeActivas($query)
    {
        return $query->where('activa', true);
    }
}
