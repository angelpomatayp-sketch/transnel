<?php

namespace Tests\Feature;

use App\Models\Almacen;
use App\Models\CentroCosto;
use App\Models\Familia;
use App\Models\Producto;
use App\Models\StockAlmacen;
use App\Models\UnidadMedida;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_reflects_registered_system_data(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@logistica.test')->firstOrFail();
        $valorInventario = (float) StockAlmacen::query()
            ->selectRaw('COALESCE(SUM(stock_actual * costo_promedio), 0) as total')
            ->value('total');

        $this
            ->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('dashboard.kpis.totalProductos', Producto::query()->where('activo', true)->count())
                ->where('dashboard.kpis.valorInventario', fn ($value) => (float) $value === $valorInventario)
                ->has('dashboard.inventarioFamilia')
                ->has('dashboard.consumoCentroCosto'));
    }

    public function test_dashboard_is_scoped_to_assigned_operation_for_almacenero(): void
    {
        $this->seed();

        $assignedWarehouse = Almacen::query()->where('codigo', 'ALM-001')->firstOrFail();
        $assignedValue = (float) StockAlmacen::query()
            ->where('almacen_id', $assignedWarehouse->id)
            ->selectRaw('COALESCE(SUM(stock_actual * costo_promedio), 0) as total')
            ->value('total');
        $assignedProductCount = Producto::query()
            ->where('activo', true)
            ->whereHas('stocks', fn ($query) => $query->where('almacen_id', $assignedWarehouse->id))
            ->count();

        $otherCenter = CentroCosto::query()->create([
            'codigo' => 'OBRA-OTRA',
            'nombre' => 'Operacion Externa',
            'tipo' => 'obra',
            'activo' => true,
        ]);
        $otherWarehouse = Almacen::query()->create([
            'codigo' => 'ALM-OTRO',
            'nombre' => 'Almacen Externo',
            'tipo' => 'obra',
            'centro_costo_id' => $otherCenter->id,
            'activo' => true,
        ]);
        $externalProduct = Producto::query()->create([
            'codigo' => 'EXT-001',
            'familia_id' => Familia::query()->firstOrFail()->id,
            'unidad_medida_id' => UnidadMedida::query()->firstOrFail()->id,
            'nombre' => 'Producto externo',
            'stock_minimo' => 0,
            'activo' => true,
        ]);

        StockAlmacen::query()->create([
            'producto_id' => $externalProduct->id,
            'almacen_id' => $otherWarehouse->id,
            'stock_actual' => 100,
            'stock_minimo' => 0,
            'costo_promedio' => 999,
        ]);

        $almacenero = User::factory()->create([
            'almacen_id' => $assignedWarehouse->id,
            'centro_costo_id' => $assignedWarehouse->centro_costo_id,
        ]);
        $almacenero->assignRole('almacenero');

        $this
            ->actingAs($almacenero)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('dashboard.kpis.valorInventario', fn ($value) => (float) $value === $assignedValue)
                ->where('dashboard.kpis.totalProductos', $assignedProductCount));
    }

    public function test_notifications_can_be_marked_as_read_for_current_session(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@logistica.test')->firstOrFail();
        $stock = StockAlmacen::query()->firstOrFail();
        $stock->update(['stock_minimo' => (float) $stock->stock_actual + 1]);

        $this
            ->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('notifications.items', fn ($items) => collect($items)->contains('key', 'stock_bajo')));

        $this
            ->actingAs($admin)
            ->post(route('notifications.read'), ['key' => 'stock_bajo'])
            ->assertRedirect();

        $this
            ->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('notifications.items', fn ($items) => collect($items)->doesntContain('key', 'stock_bajo')));
    }
}
