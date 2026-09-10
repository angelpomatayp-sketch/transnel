<?php

namespace Tests\Feature;

use App\Models\Almacen;
use App\Models\Kardex;
use App\Models\Movimiento;
use App\Models\Producto;
use App\Models\StockAlmacen;
use App\Models\User;
use App\Services\InventarioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InventarioMovimientoTest extends TestCase
{
    use RefreshDatabase;

    public function test_initial_inventory_seed_creates_stock_and_kardex(): void
    {
        $this->seed();

        $this->assertTrue(StockAlmacen::query()->exists());
        $this->assertTrue(Movimiento::query()->where('tipo', 'ENTRADA')->exists());
        $this->assertTrue(Kardex::query()->where('cantidad_entrada', '>', 0)->exists());
    }

    public function test_inventory_service_applies_weighted_average_and_output(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@logistica.test')->firstOrFail();
        $almacen = Almacen::query()->where('codigo', 'ALM-001')->firstOrFail();
        $producto = Producto::query()->where('codigo', 'CONS-TRA-001')->firstOrFail();
        $service = app(InventarioService::class);

        $service->registrarMovimiento([
            'tipo' => 'ENTRADA',
            'almacen_destino_id' => $almacen->id,
            'usuario_id' => $admin->id,
            'fecha' => '2026-08-18',
            'documento' => 'TEST-ENT',
            'detalles' => [[
                'producto_id' => $producto->id,
                'cantidad' => 10,
                'costo_unitario' => 10,
            ]],
        ]);

        $stock = StockAlmacen::query()
            ->where('producto_id', $producto->id)
            ->where('almacen_id', $almacen->id)
            ->firstOrFail();

        $this->assertSame('60.0000', $stock->stock_actual);
        $this->assertSame('5.4167', $stock->costo_promedio);

        $service->registrarMovimiento([
            'tipo' => 'SALIDA',
            'almacen_origen_id' => $almacen->id,
            'usuario_id' => $admin->id,
            'fecha' => '2026-08-18',
            'documento' => 'TEST-SAL',
            'detalles' => [[
                'producto_id' => $producto->id,
                'cantidad' => 5,
            ]],
        ]);

        $stock->refresh();

        $this->assertSame('55.0000', $stock->stock_actual);
        $this->assertSame('5.4167', $stock->costo_promedio);
    }

    public function test_inventory_service_blocks_negative_stock(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@logistica.test')->firstOrFail();
        $almacen = Almacen::query()->where('codigo', 'ALM-001')->firstOrFail();
        $producto = Producto::query()->where('codigo', 'CONS-TRA-001')->firstOrFail();

        $this->expectException(ValidationException::class);

        app(InventarioService::class)->registrarMovimiento([
            'tipo' => 'SALIDA',
            'almacen_origen_id' => $almacen->id,
            'usuario_id' => $admin->id,
            'fecha' => '2026-08-18',
            'documento' => 'TEST-NEG',
            'detalles' => [[
                'producto_id' => $producto->id,
                'cantidad' => 999999,
            ]],
        ]);
    }

    public function test_administrator_can_register_inventory_adjustment(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@logistica.test')->firstOrFail();
        $almacen = Almacen::query()->where('codigo', 'ALM-001')->firstOrFail();
        $producto = Producto::query()->where('codigo', 'CONS-TRA-001')->firstOrFail();

        $stock = StockAlmacen::query()
            ->where('producto_id', $producto->id)
            ->where('almacen_id', $almacen->id)
            ->firstOrFail();

        $stockFisico = (float) $stock->stock_actual + 7;

        $this->actingAs($admin)
            ->post(route('inventario.movimientos.adjust'), [
                'almacen_id' => $almacen->id,
                'producto_id' => $producto->id,
                'stock_fisico' => $stockFisico,
                'costo_unitario' => 6.5,
                'fecha' => '2026-08-18',
                'motivo' => 'Conteo fisico de prueba',
            ])
            ->assertRedirect();

        $stock->refresh();

        $this->assertSame(number_format($stockFisico, 4, '.', ''), $stock->stock_actual);
        $this->assertDatabaseHas('movimientos', [
            'tipo' => 'AJUSTE_ENTRADA',
            'documento' => 'REAJUSTE',
        ]);
    }

    public function test_administrator_can_open_inventory_movement_pages(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@logistica.test')->firstOrFail();

        $this->actingAs($admin);

        $this->get(route('inventario.stock.index'))->assertOk();
        $this->get(route('inventario.movimientos.index'))->assertOk();
        $this->get(route('inventario.kardex.index'))->assertOk();
    }
}
