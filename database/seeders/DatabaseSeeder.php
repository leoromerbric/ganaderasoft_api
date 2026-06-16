<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Catálogos base (sin dependencias)
        $this->call([
            RoleSeeder::class,
            TipoAnimalSeeder::class,
            TipoTrabajadorSeeder::class,
            EstadoSaludSeeder::class,
            DiaPalpacionSeeder::class,
            FoliculoSeeder::class,
            EtapaSeeder::class,
            ComposicionRazaSeeder::class,
            SanidadSeeder::class,

            // Usuarios y personas
            AdminUserSeeder::class,

            // Datos de prueba (Reemplaza al viejo TIMTestSeeder)
            DemoDataSeeder::class,
        ]);
    }
}
