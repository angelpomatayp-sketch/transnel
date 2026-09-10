<?php

namespace Tests\Feature;

use App\Models\Almacen;
use App\Models\Kardex;
use App\Models\Producto;
use App\Models\Requerimiento;
use App\Models\StockAlmacen;
use App\Models\User;
use App\Models\ValeSalida;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequerimientosValesTest extends TestCase
{
    use RefreshDatabase;

    public function test_requirement_to_vale_delivery_discounts_stock(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@logistica.test')->firstOrFail();
        $almacen = Almacen::where('codigo', 'ALM-001')->firstOrFail();
        $producto = Producto::where('codigo', 'CONS-TRA-001')->firstOrFail();
        $stockAntes = (float) StockAlmacen::where('producto_id', $producto->id)->where('almacen_id', $almacen->id)->firstOrFail()->stock_actual;

        $this->actingAs($admin);

        $this->post(route('operaciones.requerimientos.store'), [
            'almacen_id' => $almacen->id,
            'centro_costo_id' => null,
            'fecha' => '2026-08-18',
            'prioridad' => 'normal',
            'motivo' => 'Prueba',
            'detalles' => [[
                'producto_id' => $producto->id,
                'cantidad_solicitada' => 2,
                'especificaciones' => null,
            ]],
        ])->assertSessionHasNoErrors();

        $req = Requerimiento::latest('id')->firstOrFail();

        $this->post(route('operaciones.requerimientos.approve', $req))->assertSessionHasNoErrors();
        $this->post(route('operaciones.requerimientos.generate-vale', $req))->assertSessionHasNoErrors();

        $vale = ValeSalida::where('requerimiento_id', $req->id)->firstOrFail();

        $this->get(route('operaciones.requerimientos.pdf', $req))->assertOk();
        $this->get(route('operaciones.vales.pdf', $vale))->assertOk();

        $this->post(route('operaciones.vales.deliver', $vale))->assertSessionHasNoErrors();

        $stockDespues = (float) StockAlmacen::where('producto_id', $producto->id)->where('almacen_id', $almacen->id)->firstOrFail()->stock_actual;

        $this->assertSame($stockAntes - 2, $stockDespues);
        $this->assertDatabaseHas('vales_salida', ['id' => $vale->id, 'estado' => 'entregado']);
        $this->assertTrue(Kardex::where('producto_id', $producto->id)->where('cantidad_salida', '>', 0)->exists());
    }

    public function test_administrator_can_open_requirement_and_vale_pages(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@logistica.test')->firstOrFail();

        $this->actingAs($admin);

        $this->get(route('operaciones.requerimientos.index'))->assertOk();
        $this->get(route('operaciones.vales.index'))->assertOk();
    }
}
