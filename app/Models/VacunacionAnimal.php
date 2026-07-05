<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VacunacionAnimal extends Model
{
    use HasFactory;

    protected $fillable = [
        'vacunacion_id',
        'animal_id',
    ];

    /**
     * Obtener vacunacion asociado/a.
     */
    public function vacunacion()
    {
        return $this->belongsTo(Vacunacion::class, 'vacunacion_id', 'id');
    }

    /**
     * Obtener animal asociado/a.
     */
    public function animal()
    {
        return $this->belongsTo(Animal::class, 'animal_id', 'id');
    }
}
