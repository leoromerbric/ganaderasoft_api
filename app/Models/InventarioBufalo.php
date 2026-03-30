<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventarioBufalo extends Model
{
    use HasFactory;

    protected $table = 'Inventario_Bufalo';
    protected $primaryKey = 'id_Inv_B';

    protected $fillable = [
        'id_Finca',
        'Num_Becerro',
        'Num_Anojo',
        'Num_Bubilla',
        'Num_Bufalo',
        'Fecha_Inventario',
    ];

    protected $casts = [
        'Fecha_Inventario' => 'date',
        'Num_Becerro' => 'integer',
        'Num_Anojo' => 'integer',
        'Num_Bubilla' => 'integer',
        'Num_Bufalo' => 'integer',
    ];

    /**
     * Get the finca that owns the inventario bufalo.
     */
    public function finca()
    {
        return $this->belongsTo(Finca::class, 'id_Finca', 'id_Finca');
    }

    /**
     * Get the total count of all buffalo types.
     */
    public function getTotalBuffaloAttribute()
    {
        return ($this->Num_Becerro ?? 0) + 
               ($this->Num_Anojo ?? 0) + 
               ($this->Num_Bubilla ?? 0) + 
               ($this->Num_Bufalo ?? 0);
    }

    /**
     * Scope a query to only include inventarios of a specific finca.
     */
    public function scopeForFinca($query, $fincaId)
    {
        return $query->where('id_Finca', $fincaId);
    }

    /**
     * Scope a query to order by date descending.
     */
    public function scopeRecent($query)
    {
        return $query->orderBy('Fecha_Inventario', 'desc');
    }
}