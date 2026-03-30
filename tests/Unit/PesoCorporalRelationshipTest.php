<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\PesoCorporal;
use App\Models\MedidasCorporales;
use App\Models\CambiosAnimal;
use App\Models\EtapaAnimal;
use App\Models\Animal;

class PesoCorporalRelationshipTest extends TestCase
{
    public function test_peso_corporal_can_load_etapa_animal_relationship()
    {
        // Create a peso corporal instance
        $pesoCorporal = new PesoCorporal([
            'Fecha_Peso' => '2024-01-01',
            'Peso' => 100.5,
            'Comentario' => 'Test',
            'peso_etapa_anid' => 1,
            'peso_etapa_etid' => 1,
        ]);
        
        // Test that the relationship is defined correctly
        $relation = $pesoCorporal->etapaAnimal();
        
        // This should not throw an "Array to string conversion" error
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasOne::class, $relation);
        
        // Verify the relationship configuration
        $this->assertEquals(EtapaAnimal::class, $relation->getRelated()::class);
        $this->assertEquals('etan_animal_id', $relation->getForeignKeyName());
        $this->assertEquals('peso_etapa_anid', $relation->getLocalKeyName());
    }

    public function test_peso_corporal_can_load_animal_relationship()
    {
        // Create a peso corporal instance
        $pesoCorporal = new PesoCorporal([
            'Fecha_Peso' => '2024-01-01',
            'Peso' => 100.5,
            'Comentario' => 'Test',
            'peso_etapa_anid' => 1,
            'peso_etapa_etid' => 1,
        ]);
        
        // Test that the relationship is defined correctly
        $relation = $pesoCorporal->animal();
        
        // This should not throw an "Array to string conversion" error
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $relation);
        
        // Verify the relationship configuration
        $this->assertEquals(Animal::class, $relation->getRelated()::class);
    }

    public function test_medidas_corporales_can_load_etapa_animal_relationship()
    {
        // Create a medidas corporales instance
        $medidasCorporales = new MedidasCorporales([
            'Altura_HC' => 120.5,
            'medida_etapa_anid' => 1,
            'medida_etapa_etid' => 1,
        ]);
        
        // Test that the relationship is defined correctly
        $relation = $medidasCorporales->etapaAnimal();
        
        // This should not throw an error
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasOne::class, $relation);
        
        // Verify the relationship configuration
        $this->assertEquals(EtapaAnimal::class, $relation->getRelated()::class);
        $this->assertEquals('etan_animal_id', $relation->getForeignKeyName());
        $this->assertEquals('medida_etapa_anid', $relation->getLocalKeyName());
    }

    public function test_cambios_animal_can_load_etapa_animal_relationship()
    {
        // Create a cambios animal instance
        $cambiosAnimal = new CambiosAnimal([
            'Fecha_Cambio' => '2024-01-01',
            'Etapa_Cambio' => 'Test',
            'Peso' => 100.5,
            'Altura' => 120.0,
            'cambios_etapa_anid' => 1,
            'cambios_etapa_etid' => 1,
        ]);
        
        // Test that the relationship is defined correctly
        $relation = $cambiosAnimal->etapaAnimal();
        
        // This should not throw an error
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasOne::class, $relation);
        
        // Verify the relationship configuration
        $this->assertEquals(EtapaAnimal::class, $relation->getRelated()::class);
        $this->assertEquals('etan_animal_id', $relation->getForeignKeyName());
        $this->assertEquals('cambios_etapa_anid', $relation->getLocalKeyName());
    }
}