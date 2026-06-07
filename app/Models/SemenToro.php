<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SemenToro extends Model
{
    use HasFactory;

    protected $table = 'semen_toro';
    protected $primaryKey = 'semen_id';

    protected $fillable = [
        'id_Toro',
        'semen_estado',
        'semen_fecha',
    ];

    protected $casts = [
        'semen_estado' => 'boolean',
        'semen_fecha'  => 'date',
    ];

    public function toro()
    {
        return $this->belongsTo(Animal::class, 'id_Toro', 'id_Animal');
    }

    public function servicios()
    {
        return $this->hasMany(ServicioAnimal::class, 'servicio_semen_id', 'semen_id');
    }

    public function scopeActivo($query)
    {
        return $query->where('semen_estado', true);
    }

    public function scopeForToro($query, $toroId)
    {
        return $query->where('id_Toro', $toroId);
    }
}
