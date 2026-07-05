<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // ── Administrador Global ──────────────────────────────────────────────
        $personaAdminId = DB::table('personas')->insertGetId([
            'cedula'     => 'V12345678',
            'nombre'     => 'Admin',
            'apellido'   => 'Global',
            'telefono'   => '0000000000',
            'status'     => 'activo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $userAdminId = DB::table('users')->insertGetId([
            'name'       => 'Administrador',
            'email'      => 'admin@ganaderasoft.com',
            'password'   => Hash::make('123456789'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('persona_user')->insert([
            'user_id'    => $userAdminId,
            'persona_id' => $personaAdminId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('role_user')->insert([
            'user_id'    => $userAdminId,
            'role_id'    => 1, // global_admin
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('administradors')->insert([
            'persona_id' => $personaAdminId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // ── Usuarios de prueba (propietarios y técnicos) ─────────────────────
        $legacyUsers = [
            [
                'cedula'         => 'V30028485',
                'nombre'         => 'Propietario',
                'apellido'       => 'Pruebas',
                'email'          => 'propietario1.ganaderosoft@yopmail.com',
                'role_id'        => 2, // propietario
                'is_propietario' => true,
            ],
            [
                'cedula'         => 'V10000001',
                'nombre'         => 'Ingeniero',
                'apellido'       => 'Test',
                'email'          => 'ingeniero1.ganaderosoft@yopmail.com',
                'role_id'        => 3, // tecnico
                'is_propietario' => false,
            ],
            [
                'cedula'         => 'V10000002',
                'nombre'         => 'Veterinario',
                'apellido'       => 'Test',
                'email'          => 'veterinario1.ganaderosoft@yopmail.com',
                'role_id'        => 3,
                'is_propietario' => false,
            ],
            [
                'cedula'         => 'V10000003',
                'nombre'         => 'Asistente',
                'apellido'       => 'Test',
                'email'          => 'asistente1.ganaderosoft@yopmail.com',
                'role_id'        => 3,
                'is_propietario' => false,
            ],
            [
                'cedula'         => 'V10000004',
                'nombre'         => 'Pedro',
                'apellido'       => 'Test',
                'email'          => 'pedro@gmail.com',
                'role_id'        => 2,
                'is_propietario' => true,
            ],
            [
                'cedula'         => 'V10000005',
                'nombre'         => 'Nuevo',
                'apellido'       => 'Test',
                'email'          => 'nuevoviernes@yopmail.com',
                'role_id'        => 2,
                'is_propietario' => true,
            ],
        ];

        foreach ($legacyUsers as $data) {
            $personaId = DB::table('personas')->insertGetId([
                'cedula'     => $data['cedula'],
                'nombre'     => $data['nombre'],
                'apellido'   => $data['apellido'],
                'telefono'   => '04140659739',
                'status'     => 'activo',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $userId = DB::table('users')->insertGetId([
                'name'       => $data['nombre'],
                'email'      => $data['email'],
                'password'   => Hash::make('123456789'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('persona_user')->insert([
                'user_id'    => $userId,
                'persona_id' => $personaId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('role_user')->insert([
                'user_id'    => $userId,
                'role_id'    => $data['role_id'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($data['is_propietario']) {
                DB::table('propietarios')->insert([
                    'persona_id' => $personaId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
