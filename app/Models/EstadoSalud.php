<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstadoSalud extends Model
{
    use HasFactory;

    protected $table = 'estado_salud';
    protected $primaryKey = 'estado_id';
    
    // This table doesn't have timestamps
    public $timestamps = false;

    protected $fillable = [
        'estado_nombre',
    ];

    /**
     * Get the estados animal for this estado salud.
     */
    public function estadosAnimal()
    {
        return $this->hasMany(EstadoAnimal::class, 'esan_fk_estado_id', 'estado_id');
    }

    /**
     * Scope a query to search by name.
     */
    public function scopeByName($query, $name)
    {
        return $query->where('estado_nombre', 'like', '%' . $name . '%');
    }
}