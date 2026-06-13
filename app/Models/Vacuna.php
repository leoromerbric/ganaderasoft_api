<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vacuna extends Model
{
    use HasFactory;

    protected $table = 'vacuna';
    protected $primaryKey = 'vacuna_id';

    protected $fillable = [
        'vacuna_nombre',
        'vacuna_descripcion',
        'activa',
    ];

    protected $casts = [
        'activa' => 'boolean',
    ];

    public function casasComerciales()
    {
        return $this->belongsToMany(
            CasaComercial::class,
            'vacuna_casa',
            'vc_vacuna_id',
            'vc_casa_id'
        )->withPivot('dosis_cantidad');
    }

    public function dosis()
    {
        return $this->hasMany(Dosis::class, 'dosis_vacuna_id', 'vacuna_id');
    }

    public function scopeByNombre($query, $nombre)
    {
        return $query->where('vacuna_nombre', 'like', "%{$nombre}%");
    }

    public function scopeActivas($query)
    {
        return $query->where('activa', true);
    }
}
