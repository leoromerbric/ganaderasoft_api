<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Propietario extends Model
{
    use HasFactory;

    protected $table = 'propietario';
    
    protected $fillable = [
        'id',
        'id_Personal',
        'Nombre',
        'Apellido',
        'Telefono',
        'archivado',
    ];

    protected $casts = [
        'archivado' => 'boolean',
    ];

    /**
     * Get the user that owns the propietario.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'id', 'id');
    }

    /**
     * Get the fincas for the propietario.
     */
    public function fincas()
    {
        return $this->hasMany(Finca::class, 'id_Propietario', 'id');
    }

    /**
     * Get the full name of the propietario.
     */
    public function getFullNameAttribute(): string
    {
        return $this->Nombre . ' ' . $this->Apellido;
    }

    /**
     * Scope a query to only include active propietarios.
     */
    public function scopeActive($query)
    {
        return $query->where('archivado', false);
    }
}