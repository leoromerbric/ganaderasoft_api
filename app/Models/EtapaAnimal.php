<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EtapaAnimal extends Model
{
    use HasFactory;

    protected $table = 'etapa_animal';
    
    // Composite primary key
    protected $primaryKey = ['etan_animal_id', 'etan_etapa_id'];
    public $incrementing = false;
    
    // This table doesn't have timestamps based on the SQL structure
    public $timestamps = false;

    protected $fillable = [
        'etan_animal_id',
        'etan_etapa_id',
        'etan_fecha_ini',
        'etan_fecha_fin',
    ];

    protected $casts = [
        'etan_fecha_ini' => 'date',
        'etan_fecha_fin' => 'date',
    ];

    /**
     * Get the animal for this etapa animal.
     */
    public function animal()
    {
        return $this->belongsTo(Animal::class, 'etan_animal_id', 'id_Animal');
    }

    /**
     * Get the etapa for this etapa animal.
     */
    public function etapa()
    {
        return $this->belongsTo(Etapa::class, 'etan_etapa_id', 'etapa_id');
    }

    /**
     * Scope a query to only include active etapas (no end date).
     */
    public function scopeActive($query)
    {
        return $query->whereNull('etan_fecha_fin');
    }

    /**
     * Scope a query to only include etapas for a specific animal.
     */
    public function scopeForAnimal($query, $animalId)
    {
        return $query->where('etan_animal_id', $animalId);
    }

    /**
     * Scope a query to filter by etapa.
     */
    public function scopeByEtapa($query, $etapaId)
    {
        return $query->where('etan_etapa_id', $etapaId);
    }

    /**
     * Check if the etapa is currently active.
     */
    public function isActive()
    {
        return is_null($this->etan_fecha_fin) || $this->etan_fecha_fin > now()->toDateString();
    }

    /**
     * Override the getKeyName method for composite keys.
     */
    public function getKeyName()
    {
        return $this->primaryKey;
    }

    /**
     * Set the keys for a save update query.
     */
    protected function setKeysForSaveQuery($query)
    {
        $keys = $this->getKeyName();
        if (!is_array($keys)) {
            return parent::setKeysForSaveQuery($query);
        }

        foreach ($keys as $keyName) {
            $query->where($keyName, '=', $this->getKeyForSaveQuery($keyName));
        }

        return $query;
    }

    /**
     * Get the primary key value for a save query.
     */
    protected function getKeyForSaveQuery($keyName = null)
    {
        if (is_null($keyName)) {
            $keyName = $this->getKeyName();
        }

        if (isset($this->original[$keyName])) {
            return $this->original[$keyName];
        }

        return $this->getAttribute($keyName);
    }
}