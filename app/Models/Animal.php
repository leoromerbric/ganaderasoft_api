<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// Relationships resolved via same namespace — no explicit imports needed in PHP 8

class Animal extends Model
{
    use HasFactory;

    protected $fillable = [
        'rebano_id',
        'nombre',
        'codigo_animal',
        'sexo',
        'fecha_nacimiento',
        'procedencia',
        'archivado',
        'composicion_raza_id',
    ];

    protected $casts = [
        'archivado' => 'boolean',
        'fecha_nacimiento' => 'date',
    ];

    /**
     * Obtener el/la rebano que posee este/a animal.
     */
    public function rebano()
    {
        return $this->belongsTo(Rebano::class, 'rebano_id', 'id');
    }

    /**
     * Obtener el/la composicion raza para este/a animal.
     */
    public function composicionRaza()
    {
        return $this->belongsTo(ComposicionRaza::class, 'composicion_raza_id', 'id');
    }

    /**
     * Obtener el/la finca a través de rebano.
     */
    public function finca()
    {
        return $this->hasOneThrough(Finca::class, Rebano::class, 'id', 'id', 'rebano_id', 'finca_id');
    }

    /**
     * Obtener los registros de peso corporal para este animal.
     */
    public function pesosCorporales()
    {
        return $this->hasMany(PesoCorporal::class, 'animal_etapa_id', 'id');
    }

    /**
     * Obtener los registros de celo para este animal.
     */
    public function registrosCelo()
    {
        return $this->hasMany(RegistroCelo::class, 'id', 'id');
    }

    /**
     * Obtener los registros de reproducción para este animal.
     */
    public function reproducciones()
    {
        return $this->hasMany(ReproduccionAnimal::class, 'id', 'id');
    }

    /**
     * Obtener los registros de servicio para este animal.
     */
    public function servicios()
    {
        return $this->hasMany(ServicioAnimal::class, 'id', 'id');
    }

    /**
     * Obtener los registros de estado para este animal.
     */
    public function estados()
    {
        return $this->hasMany(EstadoAnimal::class, 'animal_id', 'id');
    }

    /**
     * Obtener el/la current active estado para este/a animal.
     */
    public function estadoActual()
    {
        return $this->hasOne(EstadoAnimal::class, 'animal_id', 'id')
            ->where(function ($query) {
                $query->whereNull('fecha_fin')
                    ->orWhere('fecha_fin', '>', now()->toDateString());
            })
            ->latest('fecha_ini');
    }

    /**
     * Obtener los registros de etapa animal para este animal.
     */
    public function etapaAnimales()
    {
        return $this->hasMany(EtapaAnimal::class, 'animal_id', 'id');
    }

    /**
     * Obtener el/la current active etapa para este/a animal.
     */
    public function etapaActual()
    {
        return $this->hasOne(EtapaAnimal::class, 'animal_id', 'id')
            ->where(function ($query) {
                $query->whereNull('fecha_fin')
                    ->orWhere('fecha_fin', '>', now()->toDateString());
            })
            ->latest('fecha_ini');
    }

    /**
     * Filtro para incluir solo animals activos/as.
     */
    public function scopeActive($query)
    {
        return $query->where('archivado', false);
    }

    /**
     * Filtro para incluir animals de un/a rebano específico/a.
     */
    public function scopeForRebano($query, $rebanoId)
    {
        return $query->where('rebano_id', $rebanoId);
    }

    /**
     * Filtro para incluir animals de un/a finca específico/a.
     */
    public function scopeForFinca($query, $fincaId)
    {
        return $query->whereHas('rebano', function ($q) use ($fincaId) {
            $q->where('finca_id', $fincaId);
        });
    }

    /**
     * Filtro para filtrar por sex.
     */
    public function scopeBySex($query, $sex)
    {
        return $query->where('sexo', $sex);
    }

    // ─── Árbol genealógico ────────────────────────────────────────────────────

    /** Registro ArbolGen donde este animal es hijo y tipo = 'Padre'. */
    public function registroPadre()
    {
        return $this->hasOne(ArbolGen::class, 'hijo_id', 'id')->where('tipo', 'Padre');
    }

    /** Registro ArbolGen donde este animal es hijo y tipo = 'Madre'. */
    public function registroMadre()
    {
        return $this->hasOne(ArbolGen::class, 'hijo_id', 'id')->where('tipo', 'Madre');
    }

    /** Animal padre de este animal. */
    public function padre()
    {
        return $this->hasOneThrough(
            Animal::class,
            ArbolGen::class,
            'hijo_id',   // FK en arbol_gen → este animal
            'id', // FK en animal
            'id', // PK de este animal
            'padre_id'   // columna en arbol_gen con el id del progenitor
        )->where('arbol_gens.tipo', 'Padre');
    }

    /** Animal madre de este animal. */
    public function madre()
    {
        return $this->hasOneThrough(
            Animal::class,
            ArbolGen::class,
            'hijo_id',
            'id',
            'id',
            'padre_id'
        )->where('arbol_gens.tipo', 'Madre');
    }

    /** Hijos donde este animal aparece como progenitor. */
    public function hijos()
    {
        return $this->hasMany(ArbolGen::class, 'padre_id', 'id');
    }
}
