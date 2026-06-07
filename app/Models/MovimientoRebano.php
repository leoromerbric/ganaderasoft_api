<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovimientoRebano extends Model
{
    use HasFactory;

    protected $table = 'movimiento_rebano';
    protected $primaryKey = 'id_Movimiento';

    protected $fillable = [
        'id_Finca',
        'id_Rebano',
        'Rebano_Destino',
        'id_Finca_Destino',
        'id_Rebano_Destino',
        'Comentario',
    ];

    public function fincaOrigen()
    {
        return $this->belongsTo(Finca::class, 'id_Finca', 'id_Finca');
    }

    public function rebanoOrigen()
    {
        return $this->belongsTo(Rebano::class, 'id_Rebano', 'id_Rebano');
    }

    public function fincaDestino()
    {
        return $this->belongsTo(Finca::class, 'id_Finca_Destino', 'id_Finca');
    }

    public function rebanoDestino()
    {
        return $this->belongsTo(Rebano::class, 'id_Rebano_Destino', 'id_Rebano');
    }

    public function animales()
    {
        return $this->hasMany(MovimientoRebanoAnimal::class, 'id_Movimiento', 'id_Movimiento');
    }

    public function scopeForFinca($query, $fincaId)
    {
        return $query->where('id_Finca', $fincaId);
    }

    public function scopeForRebano($query, $rebanoId)
    {
        return $query->where('id_Rebano', $rebanoId);
    }
}
