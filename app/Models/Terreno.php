<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Terreno extends Model
{
    use HasFactory;

    protected $table = 'terreno';
    protected $primaryKey = 'id_Terreno';

    protected $fillable = [
        'id_Finca',
        'Superficie',
        'Relieve',
        'Suelo_Textura',
        'ph_Suelo',
        'Precipitacion',
        'Velocidad_Viento',
        'Temp_Anual',
        'Temp_Min',
        'Temp_Max',
        'Radiacion',
        'Fuente_Agua',
        'Caudal_Disponible',
        'Riego_Metodo',
    ];

    protected $casts = [
        'Superficie' => 'float',
        'Precipitacion' => 'float',
        'Velocidad_Viento' => 'float',
        'Radiacion' => 'float',
        'Caudal_Disponible' => 'integer',
    ];

    /**
     * Get the finca that owns this terreno.
     */
    public function finca()
    {
        return $this->belongsTo(Finca::class, 'id_Finca', 'id_Finca');
    }

    /**
     * Scope a query to filter by finca.
     */
    public function scopeForFinca($query, $fincaId)
    {
        return $query->where('id_Finca', $fincaId);
    }

    /**
     * Scope a query to filter by relieve.
     */
    public function scopeByRelieve($query, $relieve)
    {
        return $query->where('Relieve', $relieve);
    }

    /**
     * Scope a query to filter by fuente de agua.
     */
    public function scopeByFuenteAgua($query, $fuenteAgua)
    {
        return $query->where('Fuente_Agua', $fuenteAgua);
    }
}