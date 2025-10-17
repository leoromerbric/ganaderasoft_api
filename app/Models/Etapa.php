<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Etapa extends Model
{
    use HasFactory;

    protected $table = 'etapa';
    protected $primaryKey = 'etapa_id';
    
    // This table doesn't have timestamps based on the SQL structure
    public $timestamps = false;

    protected $fillable = [
        'etapa_nombre',
        'etapa_edad_ini',
        'etapa_edad_fin',
        'etapa_fk_tipo_animal_id',
        'etapa_sexo',
    ];

    protected $casts = [
        'etapa_edad_ini' => 'integer',
        'etapa_edad_fin' => 'integer',
    ];

    /**
     * Get the tipo animal for this etapa.
     */
    public function tipoAnimal()
    {
        return $this->belongsTo(TipoAnimal::class, 'etapa_fk_tipo_animal_id', 'tipo_animal_id');
    }

    /**
     * Get the etapa animal records for this etapa.
     */
    public function etapaAnimales()
    {
        return $this->hasMany(EtapaAnimal::class, 'etan_etapa_id', 'etapa_id');
    }

    /**
     * Scope a query to search by name.
     */
    public function scopeByName($query, $name)
    {
        return $query->where('etapa_nombre', 'like', '%' . $name . '%');
    }

    /**
     * Scope a query to filter by tipo animal.
     */
    public function scopeForTipoAnimal($query, $tipoAnimalId)
    {
        return $query->where('etapa_fk_tipo_animal_id', $tipoAnimalId);
    }

    /**
     * Scope a query to filter by sexo.
     */
    public function scopeBySexo($query, $sexo)
    {
        return $query->where('etapa_sexo', $sexo);
    }

    /**
     * Check if an age falls within this etapa.
     */
    public function includesAge($age)
    {
        if ($this->etapa_edad_fin === null) {
            return $age >= $this->etapa_edad_ini;
        }
        
        return $age >= $this->etapa_edad_ini && $age <= $this->etapa_edad_fin;
    }
}