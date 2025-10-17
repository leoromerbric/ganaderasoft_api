<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoAnimal extends Model
{
    use HasFactory;

    protected $table = 'tipo_animal';
    protected $primaryKey = 'tipo_animal_id';
    
    // This table doesn't have timestamps based on the SQL structure
    public $timestamps = false;

    protected $fillable = [
        'tipo_animal_nombre',
    ];

    /**
     * Scope a query to search by name.
     */
    public function scopeByName($query, $name)
    {
        return $query->where('tipo_animal_nombre', 'like', '%' . $name . '%');
    }
}