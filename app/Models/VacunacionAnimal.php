<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VacunacionAnimal extends Model
{
    use HasFactory;

    protected $table = 'vacunacion_animal';
    protected $primaryKey = 'va_id';

    protected $fillable = [
        'va_vacunacion_id',
        'va_animal_id',
    ];

    public function vacunacion()
    {
        return $this->belongsTo(Vacunacion::class, 'va_vacunacion_id', 'vacunacion_id');
    }

    public function animal()
    {
        return $this->belongsTo(Animal::class, 'va_animal_id', 'id_Animal');
    }
}
