<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\MedidasCorporales;
use App\Models\EtapaAnimal;
use App\Models\Animal;

class MedidasCorporalesRelationshipTest extends TestCase
{
    public function test_medidas_corporales_can_load_etapa_animal_relationship()
    {
        // Create a medidas corporales instance
        $medidasCorporales = new MedidasCorporales([
            'Altura_HC' => 120.5,
            'Altura_HG' => 115.3,
            'medida_etapa_anid' => 1,
            'medida_etapa_etid' => 1,
        ]);
        
        // Test that the relationship is defined correctly
        $relation = $medidasCorporales->etapaAnimal();
        
        // This should not throw an "Array to string conversion" error
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasOne::class, $relation);
        
        // Verify the relationship configuration
        $this->assertEquals(EtapaAnimal::class, $relation->getRelated()::class);
        $this->assertEquals('etan_animal_id', $relation->getForeignKeyName());
        $this->assertEquals('medida_etapa_anid', $relation->getLocalKeyName());
    }

    public function test_medidas_corporales_can_load_animal_relationship()
    {
        // Create a medidas corporales instance
        $medidasCorporales = new MedidasCorporales([
            'Altura_HC' => 120.5,
            'Altura_HG' => 115.3,
            'medida_etapa_anid' => 1,
            'medida_etapa_etid' => 1,
        ]);
        
        // Test that the relationship is defined correctly
        $relation = $medidasCorporales->animal();
        
        // This should not throw an "Array to string conversion" error
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $relation);
        
        // Verify the relationship configuration
        $this->assertEquals(Animal::class, $relation->getRelated()::class);
    }
}