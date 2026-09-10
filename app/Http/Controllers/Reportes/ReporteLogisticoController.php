<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use App\Models\Almacen;
use App\Models\CentroCosto;
use App\Models\Kardex;
use App\Models\Producto;
use App\Models\StockAlmacen;
use App\Services\AccessControlService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ReporteLogisticoController extends Controller
{
    private const REPORTS = [
        'kardex' => 'Kardex Valorizado',
        'inventario' => 'Inventario Valorizado',
        'stock_bajo' => 'Stock Critico',
    ];

    public function __invoke(Request $request, AccessControlService $access): Response
    {
        return Inertia::render('Reportes/Logistico/Index', $this->buildReport($request, $access));
    }

    public function pdf(Request $request, AccessControlService $access): HttpResponse
    {
        $data = $this->buildReport($request, $access);
        $filename = 'reporte-'.str_replace('_', '-', $data['filters']['tipo']).'.pdf';

        return Pdf::loadView('pdf.reporte-logistico', $data)
            ->setPaper('a4', 'landscape')
            ->stream($filename);
    }

    private function buildReport(Request $request, AccessControlService $access): array
    {
        $filters = $this->filters($request);
        $user = $request->user();
        $report = match ($filters['tipo']) {
            'inventario' => $this->inventarioValorizado($filters, $user, $access),
            'stock_bajo' => $this->stockCritico($filters, $user, $access),
            default => $this->kardexValorizado($filters, $user, $access),
        };

        return [
            'scope' => $this->scopeLabel($user, $access),
            'tabs' => self::REPORTS,
            'filters' => $filters,
            'filterLabels' => $this->filterLabels($filters),
            'selectors' => [
                'productos' => Producto::query()->orderBy('nombre')->get(['id', 'codigo', 'nombre']),
                'almacenes' => $this->scopeAlmacen(Almacen::query(), $user, $access)->orderBy('nombre')->get(['id', 'codigo', 'nombre']),
                'centros' => CentroCosto::query()->orderBy('nombre')->get(['id', 'codigo', 'nombre']),
            ],
            'report' => ['title' => self::REPORTS[$filters['tipo']], ...$report],
        ];
    }

    private function filters(Request $request): array
    {
        $tipo = $request->input('tipo', 'kardex');
        $tipo = array_key_exists($tipo, self::REPORTS) ? $tipo : 'kardex';

        return [
            'tipo' => $tipo,
            'buscar' => $request->string('buscar')->toString(),
            'producto_id' => $request->input('producto_id'),
            'almacen_id' => $request->input('almacen_id'),
            'centro_costo_id' => $request->input('centro_costo_id'),
            'desde' => $request->input('desde', now()->startOfMonth()->toDateString()),
            'hasta' => $request->input('hasta', now()->endOfMonth()->toDateString()),
        ];
    }

    private function kardexValorizado(array $filters, $user, AccessControlService $access): array
    {
        $baseQuery = $this->filteredKardexQuery($filters, $user, $access)
            ->whereBetween('fecha', [$filters['desde'], $filters['hasta']]);

        $totals = (clone $baseQuery)
            ->selectRaw('
                COALESCE(SUM(cantidad_entrada), 0) as cantidad_entrada,
                COALESCE(SUM(cantidad_salida), 0) as cantidad_salida,
                COALESCE(SUM(total_entrada), 0) as total_entrada,
                COALESCE(SUM(total_salida), 0) as total_salida
            ')
            ->first();

        $balanceIds = $this->filteredKardexQuery($filters, $user, $access)
            ->whereDate('fecha', '<=', $filters['hasta'])
            ->selectRaw('MAX(kardex.id) as id')
            ->groupBy('producto_id', 'almacen_id');

        $balances = Kardex::query()
            ->whereIn('id', $balanceIds)
            ->get(['saldo_cantidad', 'saldo_total']);

        $rows = (clone $baseQuery)
            ->with(['producto:id,codigo,nombre', 'almacen:id,codigo,nombre', 'movimiento:id,numero,documento,centro_costo_id,estado'])
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->limit(500)
            ->get();

        return [
            'columns' => ['Fecha', 'Codigo', 'Producto', 'Almacen', 'Tipo', 'Documento', 'Entrada Cant.', 'Salida Cant.', 'Saldo Cant.', 'Saldo Valor'],
            'summary' => [
                ['label' => 'Total Entradas', 'value' => (float) $totals->cantidad_entrada, 'type' => 'number', 'tone' => 'green'],
                ['label' => 'Total Salidas', 'value' => (float) $totals->cantidad_salida, 'type' => 'number', 'tone' => 'red'],
                ['label' => 'Valor Entradas', 'value' => (float) $totals->total_entrada, 'type' => 'money', 'tone' => 'green'],
                ['label' => 'Valor Salidas', 'value' => (float) $totals->total_salida, 'type' => 'money', 'tone' => 'red'],
                ['label' => 'Saldo Cantidad', 'value' => $balances->sum(fn (Kardex $row) => (float) $row->saldo_cantidad), 'type' => 'number', 'tone' => 'blue'],
                ['label' => 'Saldo Valor', 'value' => $balances->sum(fn (Kardex $row) => (float) $row->saldo_total), 'type' => 'money', 'tone' => 'blue'],
            ],
            'rows' => $rows->map(fn (Kardex $row) => [
                $row->fecha?->format('d/m/Y'),
                $row->producto?->codigo,
                $row->producto?->nombre,
                $row->almacen?->nombre,
                strtoupper($row->tipo ?? '-'),
                $row->movimiento?->documento ?: $row->movimiento?->numero,
                (float) $row->cantidad_entrada,
                (float) $row->cantidad_salida,
                (float) $row->saldo_cantidad,
                (float) $row->saldo_total,
            ])->all(),
        ];
    }

    private function filteredKardexQuery(array $filters, $user, AccessControlService $access): Builder
    {
        return $this->scopeKardex(Kardex::query(), $user, $access)
            ->whereHas('movimiento', fn ($query) => $query->where('estado', '<>', 'anulado'))
            ->when($filters['producto_id'], fn ($query, $value) => $query->where('producto_id', $value))
            ->when($filters['almacen_id'], fn ($query, $value) => $query->where('almacen_id', $value))
            ->when($filters['centro_costo_id'], fn ($query, $value) => $query->whereHas('movimiento', fn ($movimiento) => $movimiento->where('centro_costo_id', $value)))
            ->when($filters['buscar'], function ($query, $value) {
                $query->where(function ($where) use ($value) {
                    $where
                        ->where('tipo', 'like', "%{$value}%")
                        ->orWhereHas('producto', fn ($producto) => $producto
                            ->where('codigo', 'like', "%{$value}%")
                            ->orWhere('nombre', 'like', "%{$value}%"))
                        ->orWhereHas('almacen', fn ($almacen) => $almacen
                            ->where('codigo', 'like', "%{$value}%")
                            ->orWhere('nombre', 'like', "%{$value}%"))
                        ->orWhereHas('movimiento', fn ($movimiento) => $movimiento
                            ->where('numero', 'like', "%{$value}%")
                            ->orWhere('documento', 'like', "%{$value}%"));
                });
            });
    }

    private function inventarioValorizado(array $filters, $user, AccessControlService $access): array
    {
        $rows = $this->scopeStock(StockAlmacen::query(), $user, $access)
            ->with(['producto:id,codigo,nombre,familia_id,unidad_medida_id', 'producto.familia:id,nombre', 'producto.unidad:id,nombre,abreviatura', 'almacen:id,codigo,nombre,centro_costo_id', 'almacen.centroCosto:id,nombre'])
            ->when($filters['producto_id'], fn ($query, $value) => $query->where('producto_id', $value))
            ->when($filters['almacen_id'], fn ($query, $value) => $query->where('almacen_id', $value))
            ->when($filters['centro_costo_id'], fn ($query, $value) => $query->whereHas('almacen', fn ($almacen) => $almacen->where('centro_costo_id', $value)))
            ->when($filters['buscar'], fn ($query, $value) => $query->whereHas('producto', fn ($producto) => $producto->where('codigo', 'like', "%{$value}%")->orWhere('nombre', 'like', "%{$value}%")))
            ->orderByDesc('stock_actual')
            ->limit(500)
            ->get();

        return [
            'columns' => ['Codigo', 'Producto', 'Familia', 'Unidad', 'Almacen', 'Centro de Costo', 'Stock', 'Costo Prom.', 'Valor'],
            'summary' => [
                ['label' => 'Productos', 'value' => $rows->count(), 'type' => 'number', 'tone' => 'blue'],
                ['label' => 'Stock Total', 'value' => $rows->sum(fn (StockAlmacen $row) => (float) $row->stock_actual), 'type' => 'number', 'tone' => 'green'],
                ['label' => 'Valor Inventario', 'value' => $rows->sum(fn (StockAlmacen $row) => (float) $row->stock_actual * (float) $row->costo_promedio), 'type' => 'money', 'tone' => 'blue'],
            ],
            'rows' => $rows->map(fn (StockAlmacen $row) => [
                $row->producto?->codigo,
                $row->producto?->nombre,
                $row->producto?->familia?->nombre,
                $row->producto?->unidad?->abreviatura ?? $row->producto?->unidad?->nombre,
                $row->almacen?->nombre,
                $row->almacen?->centroCosto?->nombre,
                (float) $row->stock_actual,
                (float) $row->costo_promedio,
                (float) $row->stock_actual * (float) $row->costo_promedio,
            ])->all(),
        ];
    }

    private function stockCritico(array $filters, $user, AccessControlService $access): array
    {
        $rows = $this->scopeStock(StockAlmacen::query(), $user, $access)
            ->with(['producto:id,codigo,nombre,familia_id,unidad_medida_id', 'producto.familia:id,nombre', 'producto.unidad:id,nombre,abreviatura', 'almacen:id,codigo,nombre,centro_costo_id'])
            ->where('stock_minimo', '>', 0)
            ->whereColumn('stock_actual', '<=', 'stock_minimo')
            ->when($filters['producto_id'], fn ($query, $value) => $query->where('producto_id', $value))
            ->when($filters['almacen_id'], fn ($query, $value) => $query->where('almacen_id', $value))
            ->when($filters['centro_costo_id'], fn ($query, $value) => $query->whereHas('almacen', fn ($almacen) => $almacen->where('centro_costo_id', $value)))
            ->when($filters['buscar'], function ($query, $value) {
                $query->where(function ($where) use ($value) {
                    $where
                        ->whereHas('producto', fn ($producto) => $producto
                            ->where('codigo', 'like', "%{$value}%")
                            ->orWhere('nombre', 'like', "%{$value}%"))
                        ->orWhereHas('almacen', fn ($almacen) => $almacen
                            ->where('codigo', 'like', "%{$value}%")
                            ->orWhere('nombre', 'like', "%{$value}%"));
                });
            })
            ->orderBy('stock_actual')
            ->limit(500)
            ->get();

        return [
            'columns' => ['Codigo', 'Producto', 'Familia', 'Unidad', 'Almacen', 'Stock Actual', 'Stock Minimo', 'Diferencia', 'Valor Actual'],
            'summary' => [
                ['label' => 'Items Criticos', 'value' => $rows->count(), 'type' => 'number', 'tone' => 'red'],
                ['label' => 'Faltante', 'value' => $rows->sum(fn (StockAlmacen $row) => max(0, (float) $row->stock_minimo - (float) $row->stock_actual)), 'type' => 'number', 'tone' => 'red'],
                ['label' => 'Valor Actual', 'value' => $rows->sum(fn (StockAlmacen $row) => (float) $row->stock_actual * (float) $row->costo_promedio), 'type' => 'money', 'tone' => 'blue'],
            ],
            'rows' => $rows->map(fn (StockAlmacen $row) => [
                $row->producto?->codigo,
                $row->producto?->nombre,
                $row->producto?->familia?->nombre,
                $row->producto?->unidad?->abreviatura ?? $row->producto?->unidad?->nombre,
                $row->almacen?->nombre,
                (float) $row->stock_actual,
                (float) $row->stock_minimo,
                (float) $row->stock_actual - (float) $row->stock_minimo,
                (float) $row->stock_actual * (float) $row->costo_promedio,
            ])->all(),
        ];
    }

    private function scopeStock(Builder $query, $user, AccessControlService $access): Builder
    {
        if ($access->mustScopeToAssignedOperation($user) && $user->almacen_id) {
            $query->where('stock_almacen.almacen_id', $user->almacen_id);
        }

        if ($access->mustScopeToAssignedOperation($user) && $user->centro_costo_id) {
            $query->whereHas('almacen', fn ($almacen) => $almacen->where('centro_costo_id', $user->centro_costo_id));
        }

        return $query;
    }

    private function scopeKardex(Builder $query, $user, AccessControlService $access): Builder
    {
        if ($access->mustScopeToAssignedOperation($user) && $user->almacen_id) {
            $query->where('kardex.almacen_id', $user->almacen_id);
        }

        if ($access->mustScopeToAssignedOperation($user) && $user->centro_costo_id) {
            $query->whereHas('movimiento', fn ($movimiento) => $movimiento->where('centro_costo_id', $user->centro_costo_id));
        }

        return $query;
    }

    private function scopeAlmacen(Builder $query, $user, AccessControlService $access): Builder
    {
        if ($access->mustScopeToAssignedOperation($user) && $user->almacen_id) {
            $query->where('id', $user->almacen_id);
        }

        if ($access->mustScopeToAssignedOperation($user) && $user->centro_costo_id) {
            $query->where('centro_costo_id', $user->centro_costo_id);
        }

        return $query;
    }

    private function scopeLabel($user, AccessControlService $access): array
    {
        if ($access->isAdmin($user) || $access->isJefeLogistica($user)) {
            return ['tipo' => 'gerencial', 'titulo' => 'Vista gerencial', 'detalle' => 'Datos consolidados del sistema'];
        }

        if ($access->isAlmacenero($user)) {
            return ['tipo' => 'operativo', 'titulo' => 'Vista por usuario', 'detalle' => 'Datos de tu almacen o centro asignado'];
        }

        return ['tipo' => 'usuario', 'titulo' => 'Vista por usuario', 'detalle' => 'Datos asociados a tu usuario'];
    }

    private function filterLabels(array $filters): array
    {
        $producto = $filters['producto_id']
            ? Producto::query()->find($filters['producto_id'], ['codigo', 'nombre'])
            : null;
        $almacen = $filters['almacen_id']
            ? Almacen::query()->find($filters['almacen_id'], ['codigo', 'nombre'])
            : null;
        $centro = $filters['centro_costo_id']
            ? CentroCosto::query()->find($filters['centro_costo_id'], ['codigo', 'nombre'])
            : null;

        return [
            'producto' => $producto ? "{$producto->codigo} - {$producto->nombre}" : 'Todos',
            'almacen' => $almacen ? "{$almacen->codigo} - {$almacen->nombre}" : 'Todos',
            'centro' => $centro ? "{$centro->codigo} - {$centro->nombre}" : 'Todos',
        ];
    }
}
