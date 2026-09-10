<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Exceptions\RoleDoesNotExist;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;

class MovimientoPermisosSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'stock.ver',
            'stock.crear',
            'movimientos.ver',
            'movimientos.crear',
            'movimientos.anular',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $roles = [
            'administrador',
            'admin',
            'super-admin',
            'super_admin',
            'jefe logistica',
            'jefe logística',
            'jefe-logistica',
            'jefe_logistica',
            'almacenero',
            'encargado almacen',
            'encargado_almacen',
        ];

        foreach ($roles as $roleName) {
            try {
                $role = Role::findByName($roleName, 'web');
            } catch (RoleDoesNotExist) {
                continue;
            }

            $isAlmacenero = in_array($roleName, [
                'almacenero',
                'encargado almacen',
                'encargado_almacen',
            ], true);

            $role->givePermissionTo($isAlmacenero ? [
                'stock.ver',
                'stock.crear',
                'movimientos.ver',
                'movimientos.crear',
            ] : $permissions);
        }

        User::query()
            ->where(function ($query) {
                if (Schema::hasColumn('users', 'email')) {
                    $query->where('email', 'admin@logistica.test')
                        ->orWhere('email', 'admin@logistica.pe');
                }

                if (Schema::hasColumn('users', 'name')) {
                    $query->orWhere('name', 'Administrador');
                }

                if (Schema::hasColumn('users', 'nombre')) {
                    $query->orWhere('nombre', 'Administrador');
                }
            })
            ->get()
            ->each(fn (User $user) => $user->givePermissionTo($permissions));
    }
}
