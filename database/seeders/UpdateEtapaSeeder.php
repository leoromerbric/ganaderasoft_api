<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UpdateEtapaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Modifica los datos de la base de datos para corregir la brecha de edad en búfalas hembras (Añoja).
        // Cambia la edad de fin de 730 a 913 días.
        DB::table('etapas')
            ->where('nombre', 'Añoja')
            ->where('tipo_animal_id', 2)
            ->update(['edad_fin' => 913]);
    }
}
