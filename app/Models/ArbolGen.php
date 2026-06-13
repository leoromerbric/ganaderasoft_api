<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArbolGen extends Model
{
    protected $table = 'arbol_gen';
    protected $primaryKey = 'id_arbol';
    public $timestamps = false;

    protected $fillable = [
        'id_hijo',
        'id_padre',
        'tipo',
    ];

    protected $casts = [
        'id_hijo' => 'integer',
        'id_padre' => 'integer',
    ];

    /** El animal hijo (el dueño de esta relación). */
    public function hijo()
    {
        return $this->belongsTo(Animal::class, 'id_hijo', 'id_Animal');
    }

    /** El animal padre/madre de este registro. */
    public function progenitor()
    {
        return $this->belongsTo(Animal::class, 'id_padre', 'id_Animal');
    }
}
