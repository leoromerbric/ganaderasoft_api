<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovimientoRebanoAnimal extends Model
{
    use HasFactory;

    protected $table = 'movimiento_rebano_animal';
    protected $primaryKey = null;
    public $incrementing = false;

    protected $fillable = [
        'id_Animal',
        'id_Movimiento',
        'Estado',
    ];

    public function animal()
    {
        return $this->belongsTo(Animal::class, 'id_Animal', 'id_Animal');
    }

    public function movimiento()
    {
        return $this->belongsTo(MovimientoRebano::class, 'id_Movimiento', 'id_Movimiento');
    }
}
