<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UpdateAnimalSexoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Update all animals with sexo 'F' to 'H'
        $updatedCount = DB::table('animals')
            ->where('sexo', 'F')
            ->update(['sexo' => 'H']);
            
        $this->command->info("Updated {$updatedCount} animals from 'F' to 'H'.");
    }
}
