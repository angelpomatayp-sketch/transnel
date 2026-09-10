<?php

namespace App\Services;

use App\Models\EppAsignacion;
use App\Models\OrdenCompra;
use App\Models\Requerimiento;
use App\Models\StockAlmacen;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class SystemNotificationService
{
    public function __construct(private readonly AccessControlService $access)
    {
    }

    public function forUser($user, array $readSignatures = []): array
    {
        if (! $user) {
            return ['total' => 0, 'items' => []];
        }

        $items = collect([
            $this->stockBajo($user),
            $this->requerimientosPendientes($user),
            $this->ordenesPorRecibir($user),
            $this->eppsPorVencer($user),
        ])
            ->filter(fn ($item) => $item['count'] > 0)
            ->map(fn ($item) => [
                ...$item,
                'signature' => $this->signature($item),
            ])
            ->reject(fn ($item) => in_array($item['signature'], $readSignatures, true))
            ->values();

        return [
            'total' => $items->sum('count'),
            'items' => $items->all(),
        ];
    }

    private function stockBajo($user): array
    {
        if (! $this->can($user, ['inventario.ver-stock', 'inventario.ver', 'stock.ver'])) {
            return $this->empty('stock_bajo', 'Stock bajo', route('inventario.inventario.index'), 'orange');
        }

        $count = $this->scopeStock(StockAlmacen::query(), $user)
            ->where('stock_minimo', '>', 0)
            ->whereColumn('stock_actual', '<=', 'stock_minimo')
            ->count();

        return [
            'key' => 'stock_bajo',
            'title' => 'Stock bajo',
            'message' => $count === 1 ? '1 producto con stock bajo' : "{$count} productos con stock bajo",
            'count' => $count,
            'href' => route('inventario.inventario.index'),
            'tone' => 'orange',
        ];
    }

    private function requerimientosPendientes($user): array
    {
        if (! $this->can($user, ['requerimientos.ver'])) {
            return $this->empty('requerimientos_pendientes', 'Requerimientos pendientes', route('operaciones.requerimientos.index'), 'yellow');
        }

        $count = $this->scopeOperationalDocument(Requerimiento::query(), 'requerimientos', $user, 'solicitante_id')
            ->whereIn('estado', ['pendiente', 'observado'])
            ->count();

        return [
            'key' => 'requerimientos_pendientes',
            'title' => 'Requerimientos pendientes',
            'message' => $count === 1 ? '1 requerimiento pendiente' : "{$count} requerimientos pendientes",
            'count' => $count,
            'href' => route('operaciones.requerimientos.index'),
            'tone' => 'yellow',
        ];
    }

    private function ordenesPorRecibir($user): array
    {
        if (! $this->can($user, ['compras.ver'])) {
            return $this->empty('ordenes_por_recibir', 'Ordenes por recibir', route('operaciones.compras.index'), 'cyan');
        }

        $count = Schema::hasTable('ordenes_compra')
            ? $this->scopeOperationalDocument(OrdenCompra::query(), 'ordenes_compra', $user, 'usuario_id')
                ->whereIn('estado', ['pendiente', 'recibido_parcial'])
                ->count()
            : 0;

        return [
            'key' => 'ordenes_por_recibir',
            'title' => 'Ordenes por recibir',
            'message' => $count === 1 ? '1 orden pendiente por recibir' : "{$count} ordenes pendientes por recibir",
            'count' => $count,
            'href' => route('operaciones.compras.index'),
            'tone' => 'cyan',
        ];
    }

    private function eppsPorVencer($user): array
    {
        if (! $this->can($user, ['epps.ver'])) {
            return $this->empty('epps_por_vencer', 'EPPs por vencer', route('operaciones.epps.index'), 'red');
        }

        $count = Schema::hasTable('epp_asignaciones')
            ? $this->scopeOperationalDocument(EppAsignacion::query(), 'epp_asignaciones', $user, null)
                ->where('estado', 'entregado')
                ->whereNotNull('fecha_vencimiento')
                ->whereBetween('fecha_vencimiento', [now()->toDateString(), now()->addDays(30)->toDateString()])
                ->count()
            : 0;

        return [
            'key' => 'epps_por_vencer',
            'title' => 'EPPs por vencer',
            'message' => $count === 1 ? '1 EPP proximo a vencer' : "{$count} EPPs proximos a vencer",
            'count' => $count,
            'href' => route('operaciones.epps.index'),
            'tone' => 'red',
        ];
    }

    private function scopeStock(Builder $query, $user): Builder
    {
        if ($this->access->mustScopeToAssignedOperation($user) && $user->almacen_id) {
            $query->where('stock_almacen.almacen_id', $user->almacen_id);
        } elseif ($this->access->mustScopeToAssignedOperation($user) && $user->centro_costo_id) {
            $query->whereIn('stock_almacen.almacen_id', function ($subquery) use ($user) {
                $subquery
                    ->select('id')
                    ->from('almacenes')
                    ->where('centro_costo_id', $user->centro_costo_id);
            });
        }

        return $query;
    }

    private function scopeOperationalDocument(Builder $query, string $table, $user, ?string $userColumn): Builder
    {
        if ($this->access->isAdmin($user) || $this->access->isJefeLogistica($user)) {
            return $query;
        }

        if ($this->access->isAlmacenero($user)) {
            if ($user->almacen_id && Schema::hasColumn($table, 'almacen_id')) {
                $query->where($table.'.almacen_id', $user->almacen_id);
            }

            if ($user->centro_costo_id && Schema::hasColumn($table, 'centro_costo_id')) {
                $query->where($table.'.centro_costo_id', $user->centro_costo_id);
            }

            return $query;
        }

        if ($userColumn && Schema::hasColumn($table, $userColumn)) {
            $query->where($table.'.'.$userColumn, $user->id);
        }

        return $query;
    }

    private function can($user, array $permissions): bool
    {
        return method_exists($user, 'hasAnyPermission') && $user->hasAnyPermission($permissions);
    }

    private function empty(string $key, string $title, string $href, string $tone): array
    {
        return [
            'key' => $key,
            'title' => $title,
            'message' => '',
            'count' => 0,
            'href' => $href,
            'tone' => $tone,
        ];
    }

    private function signature(array $item): string
    {
        return "{$item['key']}:{$item['count']}";
    }
}
