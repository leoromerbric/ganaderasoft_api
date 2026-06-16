<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // ==========================================
        // 1. OBTENER IDS BASE (de los seeders previos)
        // ==========================================
        $propietarioId = DB::table('propietarios')->first()->id ?? 1;
        $personaId = DB::table('personas')->first()->id ?? 1;
        $razaId = DB::table('composicion_razas')->first()->id ?? 1;
        $vacaEtapaId = DB::table('etapas')->where('nombre', 'Vaca')->first()->id ?? 8;
        $toroEtapaId = DB::table('etapas')->where('nombre', 'Toro')->first()->id ?? 7;
        $estadoSanoId = DB::table('estado_saluds')->where('nombre', 'Sano')->first()->id ?? 1;
        $tipoTrabajadorId = DB::table('tipo_trabajadors')->where('nombre', 'Operario')->first()->id ?? 3;
        $vacunaId = DB::table('vacunas')->first()->id ?? 1;
        $casaComercialId = DB::table('casa_comercials')->first()->id ?? 1;

        // ==========================================
        // 2. ESTRUCTURA FÍSICA (Finca, Terrenos, Hierros, Personal)
        // ==========================================
        $fincaId = DB::table('fincas')->insertGetId([
            'propietario_id' => $propietarioId,
            'nombre' => 'Hacienda El Paraíso',
            'explotacion_tipo' => 'Doble Propósito',
            'archivado' => false,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        DB::table('finca_user')->insert([
            'finca_id' => $fincaId,
            'user_id' => 1, // Admin global
            'created_at' => now(),
            'updated_at' => now()
        ]);

        DB::table('terrenos')->insert([
            'finca_id' => $fincaId,
            'superficie' => 50.5,
            'relieve' => 'Plano',
            'suelo_textura' => 'Franco',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        DB::table('hierros')->insert([
            'finca_id' => $fincaId,
            'propietario_id' => $propietarioId,
            'identificador' => 'HP-01',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $personalFincaId = DB::table('personal_fincas')->insertGetId([
            'finca_id' => $fincaId,
            'persona_id' => $personaId,
            'tipo_trabajador_id' => $tipoTrabajadorId,
            'status' => 'active',
            'fecha_ingreso' => '2020-01-15',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // ==========================================
        // 3. REBAÑOS Y ANIMALES
        // ==========================================
        $rebanoId = DB::table('rebanos')->insertGetId([
            'finca_id' => $fincaId,
            'nombre' => 'Producción Leche',
            'archivado' => false,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $animalNames = ['Estrella', 'Luna', 'Aurora', 'Mariposa', 'Princesa'];
        $animalIds = [];

        // Insertar un Toro para el rebaño (Padre potencial)
        $toroId = DB::table('animals')->insertGetId([
            'rebano_id' => $rebanoId,
            'nombre' => 'Sultán',
            'codigo_animal' => 'TOR-001',
            'sexo' => 'M',
            'fecha_nacimiento' => '2016-05-10',
            'procedencia' => 'Compra externa',
            'composicion_raza_id' => $razaId,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        DB::table('animal_etapa')->insert([
            'animal_id' => $toroId,
            'etapa_id' => $toroEtapaId,
            'fecha_ini' => '2018-05-10',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        foreach ($animalNames as $index => $name) {
            $animalId = DB::table('animals')->insertGetId([
                'rebano_id' => $rebanoId,
                'nombre' => $name,
                'codigo_animal' => 'VAC-00' . ($index + 1),
                'sexo' => 'H',
                'fecha_nacimiento' => '2018-06-15',
                'composicion_raza_id' => $razaId,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            $animalIds[] = $animalId;

            // Etapa
            $animalEtapaId = DB::table('animal_etapa')->insertGetId([
                'animal_id' => $animalId,
                'etapa_id' => $vacaEtapaId,
                'fecha_ini' => '2020-06-15',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Árbol Genealógico
            DB::table('arbol_gens')->insert([
                'hijo_id' => $animalId,
                'padre_id' => $toroId,
                'tipo' => 'Padre',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Estado de Salud
            DB::table('animal_estado_salud')->insert([
                'animal_id' => $animalId,
                'estado_salud_id' => $estadoSanoId,
                'fecha_ini' => '2020-06-15',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // ==========================================
            // 4. PRODUCCIÓN (Lactancias y Leches)
            // ==========================================
            $lactId = DB::table('lactancias')->insertGetId([
                'animal_etapa_id' => $animalEtapaId,
                'fecha_inicio' => Carbon::now()->subDays(150)->format('Y-m-d'),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Simulamos 5 pesajes de leche mensuales
            for ($i = 0; $i < 5; $i++) {
                DB::table('leches')->insert([
                    'lactancia_id' => $lactId,
                    'fecha_pesaje' => Carbon::now()->subDays(150 - ($i * 30))->format('Y-m-d'),
                    'pesaje_total' => rand(15, 25) + (rand(0, 9) / 10),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            // ==========================================
            // 5. MEDIDAS Y PESOS CORPORALES
            // ==========================================
            DB::table('peso_corporals')->insert([
                'animal_etapa_id' => $animalEtapaId,
                'fecha_peso' => now()->format('Y-m-d'),
                'peso' => rand(400, 550),
                'comentario' => 'Peso estimado',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::table('medidas_corporales')->insert([
                'animal_etapa_id' => $animalEtapaId,
                'altura_hc' => rand(130, 145),
                'perimetro_pt' => rand(180, 200),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // ==========================================
            // 6. REPRODUCCIÓN (Palpación y Preñez)
            // ==========================================
            $palpacionId = DB::table('palpacions')->insertGetId([
                'personal_finca_id' => $personalFincaId,
                'tipo' => 'Chequeo',
                'fecha' => Carbon::now()->subDays(60)->format('Y-m-d'),
                'animal_etapa_id' => $animalEtapaId,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::table('prenez_dias')->insert([
                'dia_palpacion_id' => 3, // Asumiendo ID 3 = 90d
                'palpacion_id' => $palpacionId,
                'tamano' => 2.50,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            // ==========================================
            // Sanidad y Salud
            // ==========================================
            // 1. Dosis (Requiere casa_comercial_vacuna_id)
            $dosisId = DB::table('dosis')->insertGetId([
                'casa_comercial_vacuna_id' => 1,
                'frecuencia' => 365,
                'costo' => 2.50,
                'costo_frasco' => 25.00,
                'fecha_uso_ini' => Carbon::now()->subMonths(6)->format('Y-m-d'),
                'animal_etapa_id' => $animalEtapaId,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            // 2. Histórico de aplicación
            DB::table('historico_aplicacions')->insert([
                'dosis_id' => $dosisId,
                'origen_tipo' => 'manual',
                'fecha_inyeccion' => Carbon::now()->subMonths(6)->format('Y-m-d'),
                'observacion' => 'Aplicación anual programada',
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            // 3. Evento de Vacunación (Evento masivo)
            $vacunacionId = DB::table('vacunacions')->insertGetId([
                'vacuna_id' => 1,
                'casa_comercial_id' => 1,
                'rebano_id' => $rebanoId,
                'modo_seleccion' => 'lista_animales',
                'fecha' => Carbon::now()->subMonths(6)->format('Y-m-d'),
                'costo_dosis' => 2.50,
                'total_animales' => 1,
                'monto_total' => 2.50,
                'observacion' => 'Vacunación demo',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // 4. Animales vacunados en el evento
            DB::table('vacunacion_animals')->insert([
                'vacunacion_id' => $vacunacionId,
                'animal_id' => $animalId,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // 5. Diagnóstico
            $diagnosticoId = DB::table('diagnosticos')->insertGetId([
                'animal_etapa_id' => $animalEtapaId,
                'descripcion' => 'Posible mastitis leve',
                'tipo' => 'Enfermedad infecciosa',
                'fecha' => Carbon::now()->subDays(10)->format('Y-m-d'),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // 6. Tratamiento
            DB::table('tratamientos')->insert([
                'diagnostico_id' => $diagnosticoId,
                'plan' => 'Antibiótico X por 5 días',
                'fecha_ini' => Carbon::now()->subDays(10)->format('Y-m-d'),
                'fecha_fin' => Carbon::now()->subDays(5)->format('Y-m-d'),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}
