<?php

namespace Tests\Feature;

use App\Models\Almacen;
use App\Models\CentroCosto;
use App\Models\Trabajador;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdministracionTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_open_administration_pages(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@logistica.test')->firstOrFail();

        $this->actingAs($admin);

        $this->get(route('administracion.usuarios.index'))->assertOk();
        $this->get(route('administracion.roles.index'))->assertOk();
        $this->get(route('administracion.almacenes.index'))->assertOk();
        $this->get(route('administracion.centros-costos.index'))->assertOk();
        $this->get(route('administracion.trabajadores.index'))->assertOk();
        $this->get(route('administracion.empresa.edit'))->assertOk();
    }

    public function test_administrator_can_create_base_administration_records(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@logistica.test')->firstOrFail();

        $this->actingAs($admin);

        $this->post(route('administracion.centros-costos.store'), [
            'codigo' => 'OBRA-TEST',
            'nombre' => 'Obra Test',
            'tipo' => 'obra',
            'responsable_id' => $admin->id,
            'ubicacion' => 'Arequipa',
            'activo' => true,
        ])->assertSessionHasNoErrors();

        $centro = CentroCosto::query()->where('codigo', 'OBRA-TEST')->firstOrFail();

        $this->post(route('administracion.almacenes.store'), [
            'codigo' => 'ALM-TEST',
            'nombre' => 'Almacen Test',
            'tipo' => 'obra',
            'ubicacion' => 'Arequipa',
            'centro_costo_id' => $centro->id,
            'responsable_id' => $admin->id,
            'activo' => true,
        ])->assertSessionHasNoErrors();

        $this->post(route('administracion.trabajadores.store'), [
            'centro_costo_id' => $centro->id,
            'nombre' => 'Trabajador Test',
            'dni' => '87654321',
            'cargo' => 'Operario',
            'telefono' => '+51 999 222 333',
            'fecha_ingreso' => '2026-08-18',
            'activo' => true,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('almacenes', ['codigo' => 'ALM-TEST']);
        $this->assertDatabaseHas('trabajadores', ['dni' => '87654321']);
        $this->assertTrue(Almacen::query()->where('codigo', 'ALM-TEST')->exists());
        $this->assertTrue(Trabajador::query()->where('dni', '87654321')->exists());
    }
}
