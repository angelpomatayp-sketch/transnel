<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class AccessControlService
{
    public const ROLE_ADMIN = 'administrador';
    public const ROLE_JEFE_LOGISTICA = 'jefe_logistica';
    public const ROLE_ALMACENERO = 'almacenero';

    public function isAdmin($user = null): bool
    {
        $user ??= Auth::user();

        return $user && method_exists($user, 'hasRole') && (
            $user->hasRole(self::ROLE_ADMIN)
            || $user->hasRole('admin')
            || $user->hasRole('super-admin')
            || $user->hasRole('super_admin')
        );
    }

    public function isJefeLogistica($user = null): bool
    {
        $user ??= Auth::user();

        return $user && method_exists($user, 'hasRole') && (
            $user->hasRole(self::ROLE_JEFE_LOGISTICA)
            || $user->hasRole('jefe logistica')
            || $user->hasRole('jefe-logistica')
        );
    }

    public function isAlmacenero($user = null): bool
    {
        $user ??= Auth::user();

        return $user && method_exists($user, 'hasRole') && (
            $user->hasRole(self::ROLE_ALMACENERO)
            || $user->hasRole('encargado almacen')
            || $user->hasRole('encargado_almacen')
            || $user->hasRole('encargado-almacen')
        );
    }

    public function mustScopeToAssignedOperation($user = null): bool
    {
        $user ??= Auth::user();

        return $this->isAlmacenero($user) && ! $this->isAdmin($user) && ! $this->isJefeLogistica($user);
    }

    public function applyOperationalScope(Builder $query, ?string $table = null, $user = null): Builder
    {
        $user ??= Auth::user();

        if (! $user || ! $this->mustScopeToAssignedOperation($user)) {
            return $query;
        }

        $model = $query->getModel();
        $table ??= $model->getTable();
        $almacenId = $user->almacen_id ?? null;
        $centroCostoId = $user->centro_costo_id ?? null;

        if ($almacenId) {
            $query->where(function (Builder $where) use ($table, $almacenId) {
                if (Schema::hasColumn($table, 'almacen_id')) {
                    $where->orWhere($table.'.almacen_id', $almacenId);
                }

                if (Schema::hasColumn($table, 'almacen_origen_id')) {
                    $where->orWhere($table.'.almacen_origen_id', $almacenId);
                }

                if (Schema::hasColumn($table, 'almacen_destino_id')) {
                    $where->orWhere($table.'.almacen_destino_id', $almacenId);
                }
            });
        }

        if ($centroCostoId && Schema::hasColumn($table, 'centro_costo_id')) {
            $query->where($table.'.centro_costo_id', $centroCostoId);
        }

        if (Schema::hasColumn($table, 'solicitante_id')) {
            $query->where(function (Builder $where) use ($table, $user) {
                $where->whereNull($table.'.solicitante_id')
                    ->orWhere($table.'.solicitante_id', $user->id);
            });
        }

        return $query;
    }

    public function abilities($user = null): array
    {
        $user ??= Auth::user();

        if (! $user) {
            return [];
        }

        return [
            'is_admin' => $this->isAdmin($user),
            'is_jefe_logistica' => $this->isJefeLogistica($user),
            'is_almacenero' => $this->isAlmacenero($user),
            'almacen_id' => $user->almacen_id ?? null,
            'centro_costo_id' => $user->centro_costo_id ?? null,
            'permissions' => method_exists($user, 'getAllPermissions')
                ? $user->getAllPermissions()->pluck('name')->values()->all()
                : [],
            'roles' => method_exists($user, 'getRoleNames')
                ? $user->getRoleNames()->values()->all()
                : [],
        ];
    }

    public function forceAssignedOperation(array $data, $user = null): array
    {
        $user ??= Auth::user();

        if (! $user || ! $this->mustScopeToAssignedOperation($user)) {
            return $data;
        }

        if ($user->almacen_id ?? null) {
            foreach (['almacen_id', 'almacen_origen_id', 'almacen_destino_id'] as $field) {
                if (array_key_exists($field, $data) || $field !== 'almacen_destino_id') {
                    $data[$field] = $user->almacen_id;
                }
            }
        }

        if ($user->centro_costo_id ?? null) {
            $data['centro_costo_id'] = $user->centro_costo_id;
        }

        return $data;
    }
}
