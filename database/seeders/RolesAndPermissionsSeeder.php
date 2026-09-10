<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'dashboard.ver',
            'usuarios.ver',
            'usuarios.crear',
            'usuarios.editar',
            'usuarios.desactivar',
            'roles.ver',
            'roles.editar',
            'almacenes.ver',
            'almacenes.crear',
            'almacenes.editar',
            'almacenes.desactivar',
            'centros-costos.ver',
            'centros-costos.crear',
            'centros-costos.editar',
            'centros-costos.desactivar',
            'trabajadores.ver',
            'trabajadores.crear',
            'trabajadores.editar',
            'trabajadores.desactivar',
            'familias.ver',
            'familias.crear',
            'familias.editar',
            'familias.desactivar',
            'unidades.ver',
            'unidades.crear',
            'unidades.editar',
            'unidades.desactivar',
            'productos.ver',
            'productos.crear',
            'productos.editar',
            'productos.desactivar',
            'productos.importar',
            'inventario.ver-stock',
            'inventario.movimientos',
            'inventario.entrada',
            'inventario.salida',
            'inventario.transferencia',
            'inventario.ajuste',
            'inventario.anular',
            'inventario.kardex',
            'requerimientos.ver',
            'requerimientos.crear',
            'requerimientos.aprobar',
            'requerimientos.rechazar',
            'requerimientos.anular',
            'vales.ver',
            'vales.crear',
            'vales.entregar',
            'vales.anular',
            'vales.pdf',
            'compras.ver',
            'compras.crear',
            'compras.aprobar',
            'compras.recibir',
            'compras.anular',
            'proveedores.ver',
            'proveedores.crear',
            'proveedores.editar',
            'proveedores.desactivar',
            'epps.ver',
            'epps.asignar',
            'epps.devolver',
            'epps.renovar',
            'epps.anular',
            'prestamos.ver',
            'prestamos.crear',
            'prestamos.devolver',
            'prestamos.renovar',
            'prestamos.anular',
            'reportes.ver',
            'reportes.exportar',
        ];

        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $administrador = Role::query()->firstOrCreate(['name' => 'administrador', 'guard_name' => 'web']);
        $jefeLogistica = Role::query()->firstOrCreate(['name' => 'jefe_logistica', 'guard_name' => 'web']);
        $almacenero = Role::query()->firstOrCreate(['name' => 'almacenero', 'guard_name' => 'web']);

        $administrador->syncPermissions($permissions);

        $jefeLogistica->syncPermissions([
            'dashboard.ver',
            'almacenes.ver',
            'centros-costos.ver',
            'trabajadores.ver',
            'familias.ver',
            'unidades.ver',
            'productos.ver',
            'inventario.ver-stock',
            'inventario.movimientos',
            'inventario.anular',
            'inventario.kardex',
            'requerimientos.ver',
            'requerimientos.aprobar',
            'requerimientos.rechazar',
            'requerimientos.anular',
            'vales.ver',
            'vales.anular',
            'vales.pdf',
            'compras.ver',
            'compras.crear',
            'compras.aprobar',
            'compras.recibir',
            'compras.anular',
            'proveedores.ver',
            'proveedores.crear',
            'proveedores.editar',
            'epps.ver',
            'prestamos.ver',
            'reportes.ver',
            'reportes.exportar',
        ]);

        $almacenero->syncPermissions([
            'dashboard.ver',
            'almacenes.ver',
            'centros-costos.ver',
            'trabajadores.ver',
            'familias.ver',
            'familias.crear',
            'familias.editar',
            'unidades.ver',
            'unidades.crear',
            'unidades.editar',
            'productos.ver',
            'productos.crear',
            'productos.editar',
            'productos.importar',
            'inventario.ver-stock',
            'inventario.movimientos',
            'inventario.entrada',
            'inventario.salida',
            'inventario.transferencia',
            'inventario.ajuste',
            'inventario.kardex',
            'requerimientos.ver',
            'vales.ver',
            'vales.crear',
            'vales.entregar',
            'vales.pdf',
            'compras.ver',
            'compras.recibir',
            'proveedores.ver',
            'epps.ver',
            'epps.asignar',
            'epps.devolver',
            'epps.renovar',
            'prestamos.ver',
            'prestamos.crear',
            'prestamos.devolver',
            'prestamos.renovar',
            'reportes.ver',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
