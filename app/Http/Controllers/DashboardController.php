<?php

namespace App\Http\Controllers;

use App\Models\EppAsignacion;
use App\Models\Kardex;
use App\Models\Movimiento;
use App\Models\OrdenCompra;
use App\Models\Producto;
use App\Models\Requerimiento;
use App\Models\StockAlmacen;
use App\Services\AccessControlService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $inicioMes = now()->startOfMonth();
        $finMes = now()->endOfMonth();

        return Inertia::render('Dashboard', [
            'dashboard' => [
                'kpis' => [
                    'valorInventario' => $this->valorInventario(),
                    'totalProductos' => $this->totalProductos(),
                    'stockBajo' => $this->stockBajo(),
                    'movimientosMes' => $this->movimientosMes($inicioMes, $finMes),
                    'requerimientosPendientes' => $this->requerimientosPendientes(),
                    'ordenesPorRecibir' => $this->ordenesPorRecibir(),
                    'consumoMes' => $this->consumoMes($inicioMes, $finMes),
                    'eppsPorVencer' => $this->eppsPorVencer(),
                ],
                'inventarioFamilia' => $this->inventarioFamilia(),
                'consumoCentroCosto' => $this->consumoCentroCosto($inicioMes, $finMes),
            ],
        ]);
    }

    private function valorInventario(): float
    {
        return (float) $this->scopeStock(StockAlmacen::query())
            ->selectRaw('COALESCE(SUM(stock_actual * costo_promedio), 0) as total')
            ->value('total');
    }

    private function totalProductos(): int
    {
        return $this->scopeProducto(Producto::query())
            ->when(Schema::hasColumn('productos', 'activo'), fn ($query) => $query->where('activo', true))
            ->count();
    }

    private function stockBajo(): int
    {
        return $this->scopeStock(StockAlmacen::query())
            ->where('stock_minimo', '>', 0)
            ->whereColumn('stock_actual', '<=', 'stock_minimo')
            ->count();
    }

    private function movimientosMes(Carbon $inicioMes, Carbon $finMes): int
    {
        return $this->scopeMovimiento(Movimiento::query())
            ->whereBetween('fecha', [$inicioMes, $finMes])
            ->where('estado', '!=', 'anulado')
            ->count();
    }

    private function requerimientosPendientes(): int
    {
        return $this->scopeRequerimiento(Requerimiento::query())
            ->whereIn('estado', ['pendiente', 'observado'])
            ->count();
    }

    private function ordenesPorRecibir(): int
    {
        if (! Schema::hasTable('ordenes_compra')) {
            return 0;
        }

        return $this->scopeOrdenCompra(OrdenCompra::query())
            ->whereIn('estado', ['pendiente', 'recibido_parcial'])
            ->count();
    }

    private function consumoMes(Carbon $inicioMes, Carbon $finMes): float
    {
        return (float) $this->scopeKardex(Kardex::query())
            ->whereBetween('fecha', [$inicioMes, $finMes])
            ->selectRaw('COALESCE(SUM(total_salida), 0) as total')
            ->value('total');
    }

    private function eppsPorVencer(): int
    {
        if (! Schema::hasTable('epp_asignaciones')) {
            return 0;
        }

        return $this->scopeEppAsignacion(EppAsignacion::query())
            ->where('estado', 'entregado')
            ->whereNotNull('fecha_vencimiento')
            ->whereBetween('fecha_vencimiento', [now()->toDateString(), now()->addDays(30)->toDateString()])
            ->count();
    }

    private function inventarioFamilia(): array
    {
        $colors = ['#263845', '#D5B23C', '#7C8992', '#A67319', '#0E7490', '#DC2626', '#7C3AED'];

        return $this->scopeStock(StockAlmacen::query())
            ->join('productos', 'productos.id', '=', 'stock_almacen.producto_id')
            ->join('familias', 'familias.id', '=', 'productos.familia_id')
            ->selectRaw('familias.nombre as name, COALESCE(SUM(stock_almacen.stock_actual * stock_almacen.costo_promedio), 0) as value')
            ->groupBy('familias.id', 'familias.nombre')
            ->orderByDesc('value')
            ->get()
            ->values()
            ->map(fn ($row, int $index) => [
                'name' => $row->name,
                'value' => round((float) $row->value, 2),
                'color' => $colors[$index % count($colors)],
            ])
            ->filter(fn ($row) => $row['value'] > 0)
            ->values()
            ->all();
    }

    private function consumoCentroCosto(Carbon $inicioMes, Carbon $finMes): array
    {
        return $this->scopeKardex(Kardex::query())
            ->join('movimientos', 'movimientos.id', '=', 'kardex.movimiento_id')
            ->leftJoin('centros_costos', 'centros_costos.id', '=', 'movimientos.centro_costo_id')
            ->whereBetween('kardex.fecha', [$inicioMes, $finMes])
            ->where('kardex.total_salida', '>', 0)
            ->selectRaw("COALESCE(centros_costos.nombre, 'Sin centro de costo') as centro, COALESCE(SUM(kardex.total_salida), 0) as total")
            ->groupBy('centros_costos.id', 'centros_costos.nombre')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'centro' => $row->centro,
                'total' => round((float) $row->total, 2),
            ])
            ->all();
    }

    private function scopeProducto(Builder $query): Builder
    {
        if (! $this->mustScopeToAssignedOperation()) {
            return $query;
        }

        $almacenId = $this->assignedAlmacenId();

        if ($almacenId) {
            $query->whereHas('stocks', fn (Builder $stock) => $stock->where('almacen_id', $almacenId));
        }

        return $query;
    }

    private function scopeStock(Builder $query): Builder
    {
        if (! $this->mustScopeToAssignedOperation()) {
            return $query;
        }

        $almacenId = $this->assignedAlmacenId();
        $centroCostoId = $this->assignedCentroCostoId();

        if ($almacenId) {
            $query->where('stock_almacen.almacen_id', $almacenId);
        } elseif ($centroCostoId) {
            $query->whereIn('stock_almacen.almacen_id', function ($subquery) use ($centroCostoId) {
                $subquery
                    ->select('id')
                    ->from('almacenes')
                    ->where('centro_costo_id', $centroCostoId);
            });
        }

        return $query;
    }

    private function scopeMovimiento(Builder $query): Builder
    {
        if (! $this->mustScopeToAssignedOperation()) {
            return $query;
        }

        $almacenId = $this->assignedAlmacenId();
        $centroCostoId = $this->assignedCentroCostoId();

        if ($almacenId) {
            $query->where(function (Builder $where) use ($almacenId) {
                $where
                    ->where('almacen_origen_id', $almacenId)
                    ->orWhere('almacen_destino_id', $almacenId);
            });
        }

        if ($centroCostoId) {
            $query->where('centro_costo_id', $centroCostoId);
        }

        return $query;
    }

    private function scopeRequerimiento(Builder $query): Builder
    {
        if (! $this->mustScopeToAssignedOperation()) {
            return $query;
        }

        if ($almacenId = $this->assignedAlmacenId()) {
            $query->where('almacen_id', $almacenId);
        }

        if ($centroCostoId = $this->assignedCentroCostoId()) {
            $query->where('centro_costo_id', $centroCostoId);
        }

        return $query;
    }

    private function scopeOrdenCompra(Builder $query): Builder
    {
        if (! $this->mustScopeToAssignedOperation()) {
            return $query;
        }

        if ($almacenId = $this->assignedAlmacenId()) {
            $query->where('almacen_id', $almacenId);
        }

        if ($centroCostoId = $this->assignedCentroCostoId()) {
            $query->where('centro_costo_id', $centroCostoId);
        }

        return $query;
    }

    private function scopeEppAsignacion(Builder $query): Builder
    {
        if (! $this->mustScopeToAssignedOperation()) {
            return $query;
        }

        if ($almacenId = $this->assignedAlmacenId()) {
            $query->where('almacen_id', $almacenId);
        }

        if ($centroCostoId = $this->assignedCentroCostoId()) {
            $query->where('centro_costo_id', $centroCostoId);
        }

        return $query;
    }

    private function scopeKardex(Builder $query): Builder
    {
        if (! $this->mustScopeToAssignedOperation()) {
            return $query;
        }

        if ($almacenId = $this->assignedAlmacenId()) {
            $query->where('kardex.almacen_id', $almacenId);
        }

        if ($centroCostoId = $this->assignedCentroCostoId()) {
            $query->whereExists(function ($subquery) use ($centroCostoId) {
                $subquery
                    ->selectRaw('1')
                    ->from('movimientos')
                    ->whereColumn('movimientos.id', 'kardex.movimiento_id')
                    ->where('movimientos.centro_costo_id', $centroCostoId);
            });
        }

        return $query;
    }

    private function mustScopeToAssignedOperation(): bool
    {
        return app(AccessControlService::class)->mustScopeToAssignedOperation(Auth::user());
    }

    private function assignedAlmacenId(): ?int
    {
        return Auth::user()?->almacen_id ? (int) Auth::user()->almacen_id : null;
    }

    private function assignedCentroCostoId(): ?int
    {
        return Auth::user()?->centro_costo_id ? (int) Auth::user()->centro_costo_id : null;
    }

}
