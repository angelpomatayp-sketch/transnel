<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class InventoryPermissionServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::before(function ($user, string $ability) {
            if (! str_starts_with($ability, 'stock.') && ! str_starts_with($ability, 'movimientos.')) {
                return null;
            }

            $allowedRoles = [
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

            if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole($allowedRoles)) {
                return true;
            }

            $roleFields = [
                $user->rol ?? null,
                $user->role ?? null,
                $user->tipo_usuario ?? null,
                $user->perfil ?? null,
            ];

            foreach ($roleFields as $roleField) {
                $roleField = is_string($roleField) ? mb_strtolower(trim($roleField)) : null;

                if ($roleField && in_array($roleField, $allowedRoles, true)) {
                    return true;
                }
            }

            return null;
        });
    }
}
