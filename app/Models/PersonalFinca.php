<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonalFinca extends Model
{
    use HasFactory;

    protected $table = 'personal_finca';
    protected $primaryKey = 'id_Tecnico';

    protected $fillable = [
        'id_Finca',
        'Cedula',
        'Nombre',
        'Apellido',
        'Telefono',
        'Correo',
        'Tipo_Trabajador',
    ];

    /**
     * Get the finca that the personal belongs to.
     */
    public function finca()
    {
        return $this->belongsTo(Finca::class, 'id_Finca', 'id_Finca');
    }

    /**
     * Scope a query to only include personal of a specific finca.
     */
    public function scopeForFinca($query, $fincaId)
    {
        return $query->where('id_Finca', $fincaId);
    }

    /**
     * Scope a query to filter by worker type.
     */
    public function scopeByTipoTrabajador($query, $tipo)
    {
        return $query->where('Tipo_Trabajador', $tipo);
    }

    /**
     * Scope a query to search by name or surname.
     */
    public function scopeByName($query, $name)
    {
        return $query->where(function($q) use ($name) {
            $q->where('Nombre', 'like', "%{$name}%")
              ->orWhere('Apellido', 'like', "%{$name}%");
        });
    }
}