<?php

namespace Tests\Feature;

use App\Models\Almacen;
use App\Models\EppAsignacion;
use App\Models\Familia;
use App\Models\Kardex;
use App\Models\Producto;
use App\Models\StockAlmacen;
use App\Models\Trabajador;
use App\Models\UnidadMedida;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReportesTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_open_logistic_report(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@logistica.test')->firstOrFail();

        $this
            ->actingAs($admin)
            ->get(route('reportes.logistico.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reportes/Logistico/Index')
                ->where('scope.tipo', 'gerencial')
                ->has('tabs')
                ->has('report.columns')
                ->has('report.summary')
                ->has('report.rows'));

        $this
            ->actingAs($admin)
            ->get(route('reportes.logistico.pdf', ['tipo' => 'inventario']))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_logistic_report_is_scoped_for_almacenero(): void
    {
        $this->seed();

        $assignedWarehouse = Almacen::query()->where('codigo', 'ALM-001')->firstOrFail();
        $assignedValue = (float) StockAlmacen::query()
            ->where('almacen_id', $assignedWarehouse->id)
            ->selectRaw('COALESCE(SUM(stock_actual * costo_promedio), 0) as total')
            ->value('total');

        $otherWarehouse = Almacen::query()->create([
            'codigo' => 'ALM-REP',
            'nombre' => 'Almacen Reporte',
            'tipo' => 'obra',
            'activo' => true,
        ]);
        $product = Producto::query()->create([
            'codigo' => 'REP-001',
            'familia_id' => Familia::query()->firstOrFail()->id,
            'unidad_medida_id' => UnidadMedida::query()->firstOrFail()->id,
            'nombre' => 'Producto de otro almacen',
            'activo' => true,
        ]);

        StockAlmacen::query()->create([
            'producto_id' => $product->id,
            'almacen_id' => $otherWarehouse->id,
            'stock_actual' => 50,
            'stock_minimo' => 0,
            'costo_promedio' => 100,
        ]);

        $almacenero = User::factory()->create([
            'almacen_id' => $assignedWarehouse->id,
            'centro_costo_id' => $assignedWarehouse->centro_costo_id,
        ]);
        $almacenero->assignRole('almacenero');

        $this
            ->actingAs($almacenero)
            ->get(route('reportes.logistico.index', ['tipo' => 'inventario']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reportes/Logistico/Index')
                ->where('scope.tipo', 'operativo')
                ->where('report.summary.2.value', fn ($value) => (float) $value === $assignedValue));
    }

    public function test_kardex_valorizado_applies_cost_center_filter(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@logistica.test')->firstOrFail();
        $centroCostoId = Almacen::query()->where('codigo', 'ALM-001')->firstOrFail()->centro_costo_id;
        $expectedEntradas = (float) Kardex::query()
            ->whereBetween('fecha', ['2026-08-01', '2026-08-31'])
            ->whereHas('movimiento', fn ($query) => $query
                ->where('centro_costo_id', $centroCostoId)
                ->where('estado', '<>', 'anulado'))
            ->sum('cantidad_entrada');

        $this
            ->actingAs($admin)
            ->get(route('reportes.logistico.index', [
                'tipo' => 'kardex',
                'centro_costo_id' => $centroCostoId,
                'desde' => '2026-08-01',
                'hasta' => '2026-08-31',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reportes/Logistico/Index')
                ->where('report.summary.0.value', fn ($value) => (float) $value === $expectedEntradas));
    }

    public function test_kardex_valorizado_uses_consolidated_final_balance(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@logistica.test')->firstOrFail();
        $desde = Kardex::query()->min('fecha');
        $hasta = Kardex::query()->max('fecha');
        $expectedValue = (float) StockAlmacen::query()
            ->selectRaw('COALESCE(SUM(stock_actual * costo_promedio), 0) as total')
            ->value('total');

        $this
            ->actingAs($admin)
            ->get(route('reportes.logistico.index', [
                'tipo' => 'kardex',
                'desde' => $desde,
                'hasta' => $hasta,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reportes/Logistico/Index')
                ->where('report.summary.5.value', fn ($value) => abs((float) $value - $expectedValue) < 0.01));
    }

    public function test_worker_epp_kardex_pdf_can_be_generated(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@logistica.test')->firstOrFail();
        $almacen = Almacen::query()->where('codigo', 'ALM-001')->firstOrFail();
        $trabajador = Trabajador::query()->create([
            'dni' => '99887766',
            'nombre' => 'Operario EPP',
            'activo' => true,
        ]);
        $producto = Producto::query()->where('es_epp', true)->firstOrFail();

        EppAsignacion::query()->create([
            'codigo' => 'EPP-TEST-001',
            'trabajador_id' => $trabajador->id,
            'producto_id' => $producto->id,
            'almacen_id' => $almacen->id,
            'centro_costo_id' => $almacen->centro_costo_id,
            'cantidad' => 1,
            'fecha_entrega' => now()->toDateString(),
            'fecha_vencimiento' => now()->addDays(20)->toDateString(),
            'estado' => 'entregado',
        ]);

        $this
            ->actingAs($admin)
            ->get(route('administracion.trabajadores.epp-kardex.pdf', $trabajador))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}
