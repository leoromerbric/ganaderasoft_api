<?php

namespace Tests\Feature\V2;

use App\Models\Animal;
use App\Models\Finca;
use App\Models\Persona;
use App\Models\Propietario;
use App\Models\Rebano;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RebanoTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withHeaders(['X-API-VERSION' => '2']);
    }

    private function createAdmin()
    {
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['code' => 'admin'], ['name' => 'Admin']);
        $user->roles()->attach($role);
        return $user;
    }

    private function createPropietario()
    {
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['code' => 'propietario'], ['name' => 'Propietario']);
        $user->roles()->attach($role);

        $persona = Persona::create([
            'cedula' => 'V' . rand(10000000, 99999999),
            'nombre' => 'Test',
            'apellido' => 'Test',
            'telefono' => '04141234567',
            'correo' => rand(1, 1000) . 'test@test.com',
            'status' => 'activo'
        ]);
        $user->personas()->attach($persona);
        
        $propietario = Propietario::create(['persona_id' => $persona->id]);

        return [$user, $propietario];
    }

    private function createFinca($propietarioId = null)
    {
        if (!$propietarioId) {
            $persona = Persona::create([
                'cedula' => 'V' . rand(10000000, 99999999),
                'nombre' => 'P2',
                'apellido' => 'P2',
                'telefono' => '04141234567',
                'correo' => rand(1, 1000) . 'test2@test.com',
                'status' => 'activo'
            ]);
            $propietario = Propietario::create(['persona_id' => $persona->id]);
            $propietarioId = $propietario->id;
        }

        return Finca::create([
            'propietario_id' => $propietarioId,
            'nombre' => 'Finca ' . rand(1, 100),
            'explotacion_tipo' => 'doble proposito',
            'archivado' => false
        ]);
    }

    private function createRebano($fincaId, $nombre = 'Rebano Test')
    {
        return Rebano::create([
            'finca_id' => $fincaId,
            'nombre' => $nombre,
            'archivado' => false
        ]);
    }

    public function test_admin_can_list_all_rebanos()
    {
        $admin = $this->createAdmin();
        $finca = $this->createFinca();
        $this->createRebano($finca->id);
        $this->createRebano($finca->id);
        $this->createRebano($finca->id);

        $response = $this->actingAs($admin)->getJson('/api/rebanos');

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['data' => [['id', 'nombre', 'archivado']]]]);
        $this->assertGreaterThanOrEqual(3, count($response->json('data.data')));
    }

    public function test_propietario_can_list_own_rebanos()
    {
        [$user, $propietario] = $this->createPropietario();
        $finca = $this->createFinca($propietario->id);
        $this->createRebano($finca->id);
        $this->createRebano($finca->id);

        $otherFinca = $this->createFinca();
        $this->createRebano($otherFinca->id);

        $response = $this->actingAs($user)->getJson('/api/rebanos');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data.data'));
    }

    public function test_propietario_without_profile_cannot_list()
    {
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['code' => 'propietario'], ['name' => 'Propietario']);
        $user->roles()->attach($role);

        $response = $this->actingAs($user)->getJson('/api/rebanos');

        $response->assertStatus(403);
    }

    public function test_admin_can_create_rebano()
    {
        $admin = $this->createAdmin();
        $finca = $this->createFinca();

        $data = [
            'finca_id' => $finca->id,
            'nombre' => 'Rebaño Test',
        ];

        $response = $this->actingAs($admin)->postJson('/api/rebanos', $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.nombre', 'Rebaño Test');
        $this->assertDatabaseHas('rebanos', ['nombre' => 'Rebaño Test', 'finca_id' => $finca->id]);
    }

    public function test_propietario_can_create_rebano_in_own_finca()
    {
        [$user, $propietario] = $this->createPropietario();
        $finca = $this->createFinca($propietario->id);

        $data = [
            'finca_id' => $finca->id,
            'nombre' => 'Mi Rebaño',
        ];

        $response = $this->actingAs($user)->postJson('/api/rebanos', $data);

        $response->assertStatus(201);
    }

    public function test_propietario_cannot_create_rebano_in_other_finca()
    {
        [$user, $propietario] = $this->createPropietario();
        $otherFinca = $this->createFinca();

        $data = [
            'finca_id' => $otherFinca->id,
            'nombre' => 'Mi Rebaño',
        ];

        $response = $this->actingAs($user)->postJson('/api/rebanos', $data);

        $response->assertStatus(403);
    }

    public function test_show_rebano()
    {
        $admin = $this->createAdmin();
        $finca = $this->createFinca();
        $rebano = $this->createRebano($finca->id);

        $response = $this->actingAs($admin)->getJson("/api/rebanos/{$rebano->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $rebano->id);
    }

    public function test_update_rebano()
    {
        $admin = $this->createAdmin();
        $finca = $this->createFinca();
        $rebano = $this->createRebano($finca->id, 'Old Name');

        $response = $this->actingAs($admin)->putJson("/api/rebanos/{$rebano->id}", [
            'nombre' => 'New Name'
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.nombre', 'New Name');
    }

    public function test_propietario_can_update_own_rebano()
    {
        [$user, $propietario] = $this->createPropietario();
        $finca = $this->createFinca($propietario->id);
        $rebano = $this->createRebano($finca->id, 'Old Name');

        $response = $this->actingAs($user)->putJson("/api/rebanos/{$rebano->id}", [
            'nombre' => 'New Name'
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('rebanos', ['id' => $rebano->id, 'nombre' => 'New Name']);
    }

    public function test_delete_rebano_without_animals()
    {
        $admin = $this->createAdmin();
        $finca = $this->createFinca();
        $rebano = $this->createRebano($finca->id);

        $response = $this->actingAs($admin)->deleteJson("/api/rebanos/{$rebano->id}");

        $response->assertStatus(200);
        $this->assertDatabaseHas('rebanos', ['id' => $rebano->id, 'archivado' => 1]);
    }

    public function test_cannot_delete_rebano_with_animals()
    {
        $admin = $this->createAdmin();
        $finca = $this->createFinca();
        $rebano = $this->createRebano($finca->id);
        
        $razaId = \DB::table('composicion_razas')->value('id') ?? \DB::table('composicion_razas')->insertGetId([
            'nombre' => 'Mestizo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Animal::create([
            'rebano_id' => $rebano->id,
            'nombre' => 'Animal',
            'codigo_animal' => '123',
            'sexo' => 'M',
            'fecha_nacimiento' => '2020-01-01',
            'composicion_raza_id' => $razaId,
            'archivado' => false
        ]);

        $response = $this->actingAs($admin)->deleteJson("/api/rebanos/{$rebano->id}");

        $response->assertStatus(409)
            ->assertJsonPath('message', 'No se puede eliminar el rebaño, tiene animales asociados');
    }

    public function test_rebano_not_found()
    {
        $admin = $this->createAdmin();
        $response = $this->actingAs($admin)->getJson("/api/rebanos/9999");
        $response->assertStatus(404);

        $responseUpdate = $this->actingAs($admin)->putJson("/api/rebanos/9999", ['nombre' => 'Test']);
        $responseUpdate->assertStatus(404);

        $responseDelete = $this->actingAs($admin)->deleteJson("/api/rebanos/9999");
        $responseDelete->assertStatus(404);
    }

    public function test_validation_fails()
    {
        $admin = $this->createAdmin();
        $response = $this->actingAs($admin)->postJson("/api/rebanos", []);
        $response->assertStatus(422);

        $rebano = $this->createRebano($this->createFinca()->id);
        $responseUpdate = $this->actingAs($admin)->putJson("/api/rebanos/{$rebano->id}", ['nombre' => str_repeat('A', 30)]);
        $responseUpdate->assertStatus(422);
    }
}
