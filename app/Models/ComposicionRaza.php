<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComposicionRaza extends Model
{
    use HasFactory;

    protected $table = 'composicion_raza';
    protected $primaryKey = 'id_Composicion';

    protected $fillable = [
        'Nombre',
        'Siglas',
        'Pelaje',
        'Proposito',
        'Tipo_Raza',
        'Origen',
        'Caracteristica_Especial',
        'Proporcion_Raza',
        'fk_id_Finca',
        'fk_tipo_animal_id',
    ];

    /**
     * Get the finca that owns this composicion raza.
     */
    public function finca()
    {
        return $this->belongsTo(Finca::class, 'fk_id_Finca', 'id_Finca');
    }

    /**
     * Get the tipo animal for this composicion raza.
     */
    public function tipoAnimal()
    {
        return $this->belongsTo(TipoAnimal::class, 'fk_tipo_animal_id', 'tipo_animal_id');
    }

    /**
     * Get the animals with this composicion raza.
     */
    public function animales()
    {
        return $this->hasMany(Animal::class, 'fk_composicion_raza', 'id_Composicion');
    }

    /**
     * Scope a query to search by name.
     */
    public function scopeByName($query, $name)
    {
        return $query->where('Nombre', 'like', '%' . $name . '%');
    }

    /**
     * Scope a query to filter by finca.
     */
    public function scopeForFinca($query, $fincaId)
    {
        return $query->where('fk_id_Finca', $fincaId);
    }
}