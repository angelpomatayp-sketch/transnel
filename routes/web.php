<?php

use App\Http\Controllers\Administracion\ProveedorController;
use App\Http\Controllers\Operaciones\CompraController;

use App\Http\Controllers\Administracion\AlmacenController;
use App\Http\Controllers\Administracion\CentroCostoController;
use App\Http\Controllers\Administracion\EmpresaConfiguracionController;
use App\Http\Controllers\Administracion\RolController;
use App\Http\Controllers\Administracion\TrabajadorController;
use App\Http\Controllers\Administracion\UsuarioController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Inventario\FamiliaController;
use App\Http\Controllers\Inventario\InventarioController;
use App\Http\Controllers\Inventario\MovimientoController;
use App\Http\Controllers\Inventario\ProductoController;
use App\Http\Controllers\Inventario\StockController;
use App\Http\Controllers\Inventario\UnidadMedidaController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Operaciones\RequerimientoController;
use App\Http\Controllers\Operaciones\ValeSalidaController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Reportes\ReporteLogisticoController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/notifications/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');

    Route::prefix('administracion')
        ->name('administracion.')
        ->group(function () {
            Route::resource('usuarios', UsuarioController::class)->except(['create', 'show', 'edit'])->middleware('permission:usuarios.ver');
            Route::resource('almacenes', AlmacenController::class)
                ->parameters(['almacenes' => 'almacen'])
                ->except(['create', 'show', 'edit'])
                ->middleware('permission:almacenes.ver');
            Route::resource('centros-costos', CentroCostoController::class)
                ->parameters(['centros-costos' => 'centroCosto'])
                ->except(['create', 'show', 'edit'])
                ->middleware('permission:centros-costos.ver');
            Route::resource('trabajadores', TrabajadorController::class)
                ->parameters(['trabajadores' => 'trabajador'])
                ->except(['create', 'show', 'edit'])
                ->middleware('permission:trabajadores.ver');
            Route::get('trabajadores/{trabajador}/epp-kardex/pdf', [TrabajadorController::class, 'eppKardexPdf'])
                ->name('trabajadores.epp-kardex.pdf')
                ->middleware('permission:trabajadores.ver|epps.ver');
            Route::resource('roles', RolController::class)
                ->except(['create', 'show', 'edit'])
                ->middleware('permission:roles.ver|usuarios.ver');
            Route::get('empresa', [EmpresaConfiguracionController::class, 'edit'])->name('empresa.edit')->middleware('permission:usuarios.ver');
            Route::put('empresa', [EmpresaConfiguracionController::class, 'update'])->name('empresa.update')->middleware('permission:usuarios.editar');
        });

    Route::prefix('inventario')
        ->name('inventario.')
        ->group(function () {
            Route::resource('familias', FamiliaController::class)
                ->except(['create', 'show', 'edit'])
                ->middleware('permission:familias.ver');
            Route::resource('unidades', UnidadMedidaController::class)
                ->parameters(['unidades' => 'unidad'])
                ->except(['create', 'show', 'edit'])
                ->middleware('permission:unidades.ver');
            Route::get('productos/exportar', [ProductoController::class, 'export'])
                ->name('productos.export')
                ->middleware('permission:productos.ver');
            Route::post('productos/importar', [ProductoController::class, 'import'])
                ->name('productos.import')
                ->middleware('permission:productos.importar|productos.crear');
            Route::resource('productos', ProductoController::class)
                ->except(['create', 'show', 'edit'])
                ->middleware('permission:productos.ver');
            Route::get('inventario', [InventarioController::class, 'index'])
                ->name('inventario.index')
                ->middleware('permission:inventario.ver-stock|inventario.ver|stock.ver');
            Route::get('stock', [StockController::class, 'index'])
                ->name('stock.index')
                ->middleware('permission:inventario.ver-stock|inventario.ver|stock.ver');
            Route::get('kardex', [StockController::class, 'kardex'])
                ->name('kardex.index')
                ->middleware('permission:inventario.kardex|kardex.ver');
            Route::get('movimientos/plantilla-importacion', [MovimientoController::class, 'template'])
                ->name('movimientos.template')
                ->middleware('permission:inventario.movimientos|movimientos.ver|movimientos.crear');
            Route::post('movimientos/importar-items', [MovimientoController::class, 'importItems'])
                ->name('movimientos.import-items')
                ->middleware('permission:inventario.movimientos|movimientos.crear');
            Route::post('movimientos/reajuste', [MovimientoController::class, 'adjust'])
                ->name('movimientos.adjust')
                ->middleware('permission:inventario.movimientos|movimientos.crear');
            Route::post('movimientos/{movimiento}/anular', [MovimientoController::class, 'cancel'])
                ->name('movimientos.cancel')
                ->middleware('permission:inventario.movimientos|movimientos.anular');
            Route::resource('movimientos', MovimientoController::class)
                ->only(['index', 'store'])
                ->middlewareFor('index', 'permission:inventario.movimientos|movimientos.ver')
                ->middlewareFor('store', 'permission:inventario.movimientos|movimientos.crear');
        });

    Route::prefix('operaciones')
        ->name('operaciones.')
        ->group(function () {
            Route::resource('requerimientos', RequerimientoController::class)
                ->only(['index', 'store', 'update'])
                ->middleware('permission:requerimientos.ver');
            Route::post('requerimientos/{requerimiento}/enviar', [RequerimientoController::class, 'send'])
                ->name('requerimientos.send')
                ->middleware('permission:requerimientos.ver');
            Route::post('requerimientos/{requerimiento}/aprobar', [RequerimientoController::class, 'approve'])
                ->name('requerimientos.approve')
                ->middleware('permission:requerimientos.aprobar');
            Route::put('requerimientos/{requerimiento}/aprobar', [RequerimientoController::class, 'approve'])
                ->name('requerimientos.approve.put')
                ->middleware('permission:requerimientos.aprobar');
            Route::put('requerimientos/{requerimiento}/observar', [RequerimientoController::class, 'observe'])
                ->name('requerimientos.observe')
                ->middleware('permission:requerimientos.aprobar');
            Route::post('requerimientos/{requerimiento}/rechazar', [RequerimientoController::class, 'reject'])
                ->name('requerimientos.reject')
                ->middleware('permission:requerimientos.rechazar');
            Route::put('requerimientos/{requerimiento}/rechazar', [RequerimientoController::class, 'rejectWithHistory'])
                ->name('requerimientos.reject.put')
                ->middleware('permission:requerimientos.rechazar');
            Route::put('requerimientos/{requerimiento}/anular', [RequerimientoController::class, 'cancel'])
                ->name('requerimientos.cancel')
                ->middleware('permission:requerimientos.aprobar');
            Route::post('requerimientos/{requerimiento}/generar-vale', [RequerimientoController::class, 'generateVale'])
                ->name('requerimientos.generate-vale')
                ->middleware('permission:vales.crear|vales.ver');
            Route::get('requerimientos/{requerimiento}/pdf', [RequerimientoController::class, 'pdf'])
                ->name('requerimientos.pdf')
                ->middleware('permission:vales.pdf|vales.ver');
            Route::resource('vales', ValeSalidaController::class)
                ->only(['index', 'store'])
                ->middleware('permission:vales.ver');
            Route::post('vales/{vale}/entregar', [ValeSalidaController::class, 'deliver'])
                ->name('vales.deliver')
                ->middleware('permission:vales.entregar');
            Route::get('vales/{vale}/pdf', [ValeSalidaController::class, 'pdf'])
                ->name('vales.pdf')
                ->middleware('permission:vales.pdf|vales.ver');
        });
});

require __DIR__.'/auth.php';

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/requerimientos', fn () => redirect()->route('operaciones.requerimientos.index'));
    Route::get('/requerimientos/vales', fn () => redirect()->route('operaciones.vales.index'));
    Route::get('/requerimientos/prestamos', fn () => redirect()->route('operaciones.prestamos.index'));
});

Route::middleware(['auth', 'permission:vales.crear|vales.ver'])->put('/operaciones/vales/{vale}', [\App\Http\Controllers\Operaciones\ValeSalidaController::class, 'update'])->name('operaciones.vales.update');
Route::middleware(['auth'])->prefix('operaciones')->name('operaciones.')->group(function () {
    Route::get('epps', [\App\Http\Controllers\Operaciones\EppController::class, 'index'])->name('epps.index')->middleware('permission:epps.ver');
    Route::get('epp', [\App\Http\Controllers\Operaciones\EppController::class, 'index'])->name('epp.index')->middleware('permission:epps.ver');
    Route::post('epps', [\App\Http\Controllers\Operaciones\EppController::class, 'store'])->name('epps.store')->middleware('permission:epps.entregar|epps.asignar');
    Route::put('epps/{asignacion}/devolver', [\App\Http\Controllers\Operaciones\EppController::class, 'devolver'])->name('epps.devolver')->middleware('permission:epps.devolver');
    Route::put('epps/{asignacion}/renovar', [\App\Http\Controllers\Operaciones\EppController::class, 'renovar'])->name('epps.renovar')->middleware('permission:epps.renovar');
    Route::get('prestamos', [\App\Http\Controllers\Operaciones\PrestamoController::class, 'index'])->name('prestamos.index')->middleware('permission:prestamos.ver');
    Route::post('prestamos', [\App\Http\Controllers\Operaciones\PrestamoController::class, 'store'])->name('prestamos.store')->middleware('permission:prestamos.crear');
    Route::put('prestamos/{prestamo}/devolver', [\App\Http\Controllers\Operaciones\PrestamoController::class, 'devolver'])->name('prestamos.devolver')->middleware('permission:prestamos.devolver');
    Route::get('prestamos/{prestamo}/pdf', [\App\Http\Controllers\Operaciones\PrestamoController::class, 'pdf'])->name('prestamos.pdf')->middleware('permission:prestamos.ver');
});

Route::middleware(['auth', 'permission:epps.ver'])->get('/inventario/epps', [\App\Http\Controllers\Operaciones\EppController::class, 'index'])->name('inventario.epps.index');
Route::middleware(['auth'])->group(function () {
    Route::resource('administracion/proveedores', ProveedorController::class)
        ->names('administracion.proveedores')
        ->except(['create', 'show', 'edit'])
        ->middleware('permission:proveedores.ver');

    Route::get('operaciones/compras', [CompraController::class, 'index'])->name('operaciones.compras.index')->middleware('permission:compras.ver');
    Route::post('operaciones/compras', [CompraController::class, 'store'])->name('operaciones.compras.store')->middleware('permission:compras.crear');
    Route::get('operaciones/compras/{compra}/pdf', [CompraController::class, 'pdf'])->name('operaciones.compras.pdf')->middleware('permission:compras.ver');
    Route::put('operaciones/compras/{compra}/recibir', [CompraController::class, 'recibir'])->name('operaciones.compras.recibir')->middleware('permission:compras.recibir');
    Route::put('operaciones/compras/{compra}/anular', [CompraController::class, 'anular'])->name('operaciones.compras.anular')->middleware('permission:compras.anular');
});
Route::middleware(['web', 'auth'])->prefix('reportes')->name('reportes.')->group(function () {
    Route::get('logistico', ReporteLogisticoController::class)->name('logistico.index')->middleware('permission:reportes.ver');
    Route::get('logistico/pdf', [ReporteLogisticoController::class, 'pdf'])->name('logistico.pdf')->middleware('permission:reportes.ver');
    Route::get('auditoria-inventario', [\App\Http\Controllers\Reportes\AuditoriaInventarioController::class, 'index'])->name('auditoria-inventario.index')->middleware('permission:auditoria.ver|reportes.ver');
});
