<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventarioVacuno extends Model
{
    use HasFactory;

    protected $table = 'inventario_vacuno';
    protected $primaryKey = 'id_Inv_V';

    protected $fillable = [
        'id_Finca',
        'Num_Becerra',
        'Num_Mauta',
        'Num_Novilla',
        'Num_Vaca',
        'Num_Becerro',
        'Num_Maute',
        'Num_Torete',
        'Num_Toro',
        'Fecha_Inventario',
    ];

    protected $casts = [
        'Num_Becerra'      => 'integer',
        'Num_Mauta'        => 'integer',
        'Num_Novilla'      => 'integer',
        'Num_Vaca'         => 'integer',
        'Num_Becerro'      => 'integer',
        'Num_Maute'        => 'integer',
        'Num_Torete'       => 'integer',
        'Num_Toro'         => 'integer',
        'Fecha_Inventario' => 'date',
    ];

    public function finca()
    {
        return $this->belongsTo(Finca::class, 'id_Finca', 'id_Finca');
    }

    public function getTotalAttribute(): int
    {
        return (int) (
            $this->Num_Becerra + $this->Num_Mauta + $this->Num_Novilla +
            $this->Num_Vaca + $this->Num_Becerro + $this->Num_Maute +
            $this->Num_Torete + $this->Num_Toro
        );
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
