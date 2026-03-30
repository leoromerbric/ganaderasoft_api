<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rebano extends Model
{
    use HasFactory;

    protected $table = 'rebano';
    protected $primaryKey = 'id_Rebano';

    protected $fillable = [
        'id_Finca',
        'Nombre',
        'archivado',
    ];

    protected $casts = [
        'archivado' => 'boolean',
    ];

    /**
     * Get the finca that owns the rebano.
     */
    public function finca()
    {
        return $this->belongsTo(Finca::class, 'id_Finca', 'id_Finca');
    }

    /**
     * Get the animals for the rebano.
     */
    public function animales()
    {
        return $this->hasMany(Animal::class, 'id_Rebano', 'id_Rebano');
    }

    /**
     * Scope a query to only include active rebanos.
     */
    public function scopeActive($query)
    {
        return $query->where('archivado', false);
    }

    /**
     * Scope a query to only include rebanos of a specific finca.
     */
    public function scopeForFinca($query, $fincaId)
    {
        return $query->where('id_Finca', $fincaId);
    }
}