<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tratamiento extends Model
{
    use HasFactory;

    protected $table = 'tratamiento';
    protected $primaryKey = 'tratamiento_id';

    protected $fillable = [
        'tratamiento_plan',
        'tratamiento_fecha_ini',
        'tratamiento_fecha_fin',
        'tratamiento_diagnostico_id',
    ];

    protected $casts = [
        'tratamiento_fecha_ini' => 'date',
        'tratamiento_fecha_fin' => 'date',
    ];

    public function diagnostico()
    {
        return $this->belongsTo(Diagnostico::class, 'tratamiento_diagnostico_id', 'diagnostico_id');
    }

    public function scopeByDateRange($query, $startDate, $endDate = null)
    {
        if ($endDate) {
            return $query->whereBetween('tratamiento_fecha_ini', [$startDate, $endDate]);
        }
        return $query->where('tratamiento_fecha_ini', '>=', $startDate);
    }

    public function scopeForDiagnostico($query, $diagnosticoId)
    {
        return $query->where('tratamiento_diagnostico_id', $diagnosticoId);
    }
}
