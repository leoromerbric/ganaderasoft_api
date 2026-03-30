<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstadoAnimal extends Model
{
    use HasFactory;

    protected $table = 'estado_animal';
    protected $primaryKey = 'esan_id';
    
    // This table doesn't have standard timestamps
    public $timestamps = false;

    protected $fillable = [
        'esan_fecha_ini',
        'esan_fecha_fin',
        'esan_fk_estado_id',
        'esan_fk_id_animal',
    ];

    protected $casts = [
        'esan_fecha_ini' => 'date',
        'esan_fecha_fin' => 'date',
    ];

    /**
     * Get the animal that has this estado.
     */
    public function animal()
    {
        return $this->belongsTo(Animal::class, 'esan_fk_id_animal', 'id_Animal');
    }

    /**
     * Get the estado salud for this estado animal.
     */
    public function estadoSalud()
    {
        return $this->belongsTo(EstadoSalud::class, 'esan_fk_estado_id', 'estado_id');
    }

    /**
     * Scope a query to only include active estados (no end date).
     */
    public function scopeActive($query)
    {
        return $query->whereNull('esan_fecha_fin');
    }

    /**
     * Scope a query to only include estados for a specific animal.
     */
    public function scopeForAnimal($query, $animalId)
    {
        return $query->where('esan_fk_id_animal', $animalId);
    }

    /**
     * Scope a query to filter by estado salud.
     */
    public function scopeByEstado($query, $estadoId)
    {
        return $query->where('esan_fk_estado_id', $estadoId);
    }

    /**
     * Check if the estado is currently active.
     */
    public function isActive()
    {
        return is_null($this->esan_fecha_fin) || $this->esan_fecha_fin > now()->toDateString();
    }
}