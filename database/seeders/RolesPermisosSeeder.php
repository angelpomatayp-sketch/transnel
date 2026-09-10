<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesPermisosSeeder extends Seeder
{
    public function run(): void
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'dashboard.ver',
            'usuarios.ver', 'usuarios.crear', 'usuarios.editar', 'usuarios.eliminar',
            'roles.ver', 'roles.editar',
            'empresa.ver', 'empresa.editar',
            'almacenes.ver', 'almacenes.crear', 'almacenes.editar', 'almacenes.eliminar',
            'centros.ver', 'centros.crear', 'centros.editar', 'centros.eliminar',
            'familias.ver', 'familias.crear', 'familias.editar', 'familias.eliminar',
            'unidades.ver', 'unidades.crear', 'unidades.editar', 'unidades.eliminar',
            'productos.ver', 'productos.crear', 'productos.editar', 'productos.eliminar', 'productos.importar',
            'inventario.ver',
            'kardex.ver',
            'movimientos.ver', 'movimientos.crear', 'movimientos.anular',
            'reajustes.crear',
            'requerimientos.ver', 'requerimientos.crear', 'requerimientos.editar', 'requerimientos.aprobar', 'requerimientos.rechazar', 'requerimientos.anular',
            'compras.ver', 'compras.crear', 'compras.recibir', 'compras.anular',
            'proveedores.ver', 'proveedores.crear', 'proveedores.editar',
            'vales.ver', 'vales.crear', 'vales.entregar', 'vales.anular',
            'trabajadores.ver', 'trabajadores.crear', 'trabajadores.editar',
            'epps.ver', 'epps.entregar', 'epps.devolver', 'epps.renovar',
            'prestamos.ver', 'prestamos.crear', 'prestamos.devolver',
            'auditoria.ver',
            'reportes.ver',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $admin = Role::findOrCreate('administrador');
        $jefe = Role::findOrCreate('jefe_logistica');
        $almacenero = Role::findOrCreate('almacenero');

        $admin->syncPermissions($permissions);

        $jefe->syncPermissions([
            'dashboard.ver',
            'almacenes.ver', 'centros.ver',
            'familias.ver', 'familias.crear', 'familias.editar',
            'unidades.ver', 'unidades.crear', 'unidades.editar',
            'productos.ver', 'productos.crear', 'productos.editar', 'productos.importar',
            'inventario.ver',
            'kardex.ver',
            'movimientos.ver', 'movimientos.crear', 'movimientos.anular',
            'reajustes.crear',
            'requerimientos.ver', 'requerimientos.crear', 'requerimientos.editar', 'requerimientos.aprobar', 'requerimientos.rechazar', 'requerimientos.anular',
            'compras.ver', 'compras.crear', 'compras.recibir', 'compras.anular',
            'proveedores.ver', 'proveedores.crear', 'proveedores.editar',
            'vales.ver', 'vales.crear', 'vales.entregar', 'vales.anular',
            'trabajadores.ver', 'trabajadores.crear', 'trabajadores.editar',
            'epps.ver', 'epps.entregar', 'epps.devolver', 'epps.renovar',
            'prestamos.ver', 'prestamos.crear', 'prestamos.devolver',
            'auditoria.ver',
            'reportes.ver',
        ]);

        $almacenero->syncPermissions([
            'dashboard.ver',
            'familias.ver',
            'unidades.ver',
            'productos.ver', 'productos.crear', 'productos.importar',
            'inventario.ver',
            'kardex.ver',
            'movimientos.ver', 'movimientos.crear',
            'requerimientos.ver', 'requerimientos.crear', 'requerimientos.editar',
            'vales.ver', 'vales.crear', 'vales.entregar',
            'trabajadores.ver',
            'epps.ver', 'epps.entregar', 'epps.devolver', 'epps.renovar',
            'prestamos.ver', 'prestamos.crear', 'prestamos.devolver',
            'compras.ver',
            'proveedores.ver',
            'reportes.ver',
        ]);
    }
}
