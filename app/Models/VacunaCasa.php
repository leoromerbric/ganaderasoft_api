<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VacunaCasa extends Model
{
    protected $table = 'vacuna_casa';
    protected $primaryKey = null;
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'vc_vacuna_id',
        'vc_casa_id',
        'dosis_cantidad',
    ];

    protected $casts = [
        'dosis_cantidad' => 'float',
    ];

    public function vacuna()
    {
        return $this->belongsTo(Vacuna::class, 'vc_vacuna_id', 'vacuna_id');
    }

    public function casaComercial()
    {
        return $this->belongsTo(CasaComercial::class, 'vc_casa_id', 'casa_id');
    }
}
