<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder {
    public function run() {
        DB::table('roles')->insert(collect([
            ['id' => 1, 'code' => 'global_admin', 'name' => 'Administrador Global', 'description' => 'Control total del sistema'],
            ['id' => 2, 'code' => 'propietario', 'name' => 'Propietario', 'description' => 'Dueño de fincas'],
            ['id' => 3, 'code' => 'tecnico', 'name' => 'Técnico', 'description' => 'Personal de la finca'],
        ])->map(fn($item) => array_merge($item, ['created_at' => now(), 'updated_at' => now()]))->toArray());
    }
}
