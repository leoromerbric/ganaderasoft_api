<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Leche extends Model
{
    use HasFactory;

    protected $table = 'leche';
    protected $primaryKey = 'leche_id';

    protected $fillable = [
        'leche_fecha_pesaje',
        'leche_pesaje_Total',
        'leche_lactancia_id',
    ];

    protected $casts = [
        'leche_fecha_pesaje' => 'date',
        'leche_pesaje_Total' => 'decimal:2',
    ];

    /**
     * Get the lactancia that owns this leche record.
     */
    public function lactancia()
    {
        return $this->belongsTo(Lactancia::class, 'leche_lactancia_id', 'lactancia_id');
    }

    /**
     * Get the animal through lactancia.
     */
    public function animal()
    {
        return $this->hasOneThrough(
            Animal::class,
            Lactancia::class,
            'lactancia_id',
            'id_Animal',
            'leche_lactancia_id',
            'lactancia_etapa_anid'
        );
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeByDateRange($query, $startDate, $endDate = null)
    {
        if ($endDate) {
            return $query->whereBetween('leche_fecha_pesaje', [$startDate, $endDate]);
        }
        return $query->where('leche_fecha_pesaje', '>=', $startDate);
    }

    /**
     * Scope a query to filter by lactancia.
     */
    public function scopeForLactancia($query, $lactanciaId)
    {
        return $query->where('leche_lactancia_id', $lactanciaId);
    }

    /**
     * Scope a query to filter by minimum production.
     */
    public function scopeMinProduction($query, $minAmount)
    {
        return $query->where('leche_pesaje_Total', '>=', $minAmount);
    }
}