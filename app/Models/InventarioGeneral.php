<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventarioGeneral extends Model
{
    use HasFactory;

    protected $table = 'inventario_general';
    protected $primaryKey = 'id_Inv';

    protected $fillable = [
        'id_Finca',
        'Num_Personal',
        'Fecha_Inventario',
    ];

    protected $casts = [
        'Num_Personal'     => 'integer',
        'Fecha_Inventario' => 'date',
    ];

    public function finca()
    {
        return $this->belongsTo(Finca::class, 'id_Finca', 'id_Finca');
    }

    public function scopeForFinca($query, $fincaId)
    {
        return $query->where('id_Finca', $fincaId);
    }

    public function scopeByDateRange($query, $startDate, $endDate = null)
    {
        if ($endDate) {
            return $query->whereBetween('Fecha_Inventario', [$startDate, $endDate]);
        }
        return $query->where('Fecha_Inventario', '>=', $startDate);
    }
}
