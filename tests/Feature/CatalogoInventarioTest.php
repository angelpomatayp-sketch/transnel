<?php

namespace Tests\Feature;

use App\Models\Familia;
use App\Models\Producto;
use App\Models\UnidadMedida;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class CatalogoInventarioTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_open_inventory_catalog_pages(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@logistica.test')->firstOrFail();

        $this->actingAs($admin);

        $this->get(route('inventario.familias.index'))->assertOk();
        $this->get(route('inventario.unidades.index'))->assertOk();
        $this->get(route('inventario.productos.index'))->assertOk();
    }

    public function test_administrator_can_create_catalog_records(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@logistica.test')->firstOrFail();

        $this->actingAs($admin);

        $this->post(route('inventario.familias.store'), [
            'codigo' => 'REP',
            'nombre' => 'Repuestos',
            'descripcion' => 'Repuestos de equipos',
            'es_epp' => false,
            'categoria_epp' => null,
            'activo' => true,
        ])->assertSessionHasNoErrors();

        $this->post(route('inventario.unidades.store'), [
            'codigo' => 'CJ',
            'nombre' => 'Caja',
            'abreviatura' => 'cja',
            'activo' => true,
        ])->assertSessionHasNoErrors();

        $familia = Familia::query()->where('codigo', 'REP')->firstOrFail();
        $unidad = UnidadMedida::query()->where('codigo', 'CJ')->firstOrFail();

        $this->post(route('inventario.productos.store'), [
            'familia_id' => $familia->id,
            'unidad_medida_id' => $unidad->id,
            'nombre' => 'Filtro de aceite',
            'descripcion' => 'Filtro para mantenimiento',
            'marca' => 'Fleetguard',
            'modelo' => 'FG-100',
            'ubicacion' => 'Rack A1',
            'lote' => null,
            'stock_minimo' => 3,
            'stock_maximo' => 30,
            'costo_referencial' => 120,
            'es_epp' => false,
            'vida_util_dias' => null,
            'dias_alerta_vencimiento' => null,
            'requiere_talla' => false,
            'tallas_texto' => null,
            'activo' => true,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('productos', ['codigo' => 'REP-001']);
        $this->assertTrue(Producto::query()->where('codigo', 'REP-001')->exists());
    }

    public function test_administrator_can_export_products(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@logistica.test')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('inventario.productos.export'))
            ->assertOk()
            ->assertHeader('content-disposition');
    }

    public function test_administrator_can_import_products_with_current_template(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@logistica.test')->firstOrFail();
        $path = tempnam(sys_get_temp_dir(), 'productos-import-').'.xlsx';

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['codigo', 'nombre', 'familia', 'unidad', 'marca', 'modelo', 'stock_min', 'activo', 'es_epp', 'categoria_epp'],
            ['', 'Zapato de seguridad T 40', 'Protección de pies', 'Par', 'CAT', '', 1, 'SI', 'SI', 'PIES'],
        ]);
        (new Xlsx($spreadsheet))->save($path);

        $this->actingAs($admin)
            ->post(route('inventario.productos.import'), [
                'archivo' => new UploadedFile($path, 'productos.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true),
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $producto = Producto::query()
            ->with(['familia', 'unidad'])
            ->where('nombre', 'Zapato de seguridad T 40')
            ->firstOrFail();

        $this->assertSame('Protección de Pies', $producto->familia->categoria_epp);
        $this->assertSame('Par', $producto->unidad->nombre);
        $this->assertTrue($producto->activo);
        $this->assertTrue($producto->es_epp);

        @unlink($path);
    }
}
