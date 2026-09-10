<?php

namespace App\Http\Controllers\Inventario;

use App\Http\Controllers\Controller;
use App\Models\Almacen;
use App\Models\Kardex;
use App\Models\Producto;
use App\Models\StockAlmacen;
use App\Services\AccessControlService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StockController extends Controller
{
    public function index(Request $request, AccessControlService $access): Response
    {
        $user = $request->user();

        return Inertia::render('Inventario/Stock/Index', [
            'stocks' => StockAlmacen::query()
                ->with(['producto:id,codigo,nombre,stock_minimo,stock_maximo', 'almacen:id,codigo,nombre'])
                ->when($access->mustScopeToAssignedOperation($user), fn ($query) => $query->where('almacen_id', $user->almacen_id))
                ->orderBy('almacen_id')
                ->orderBy('producto_id')
                ->get(),
        ]);
    }

    public function kardex(Request $request, AccessControlService $access): Response
    {
        $user = $request->user();
        $scopedToAssignedWarehouse = $access->mustScopeToAssignedOperation($user);
        $filters = $request->only([
            'producto_id',
            'almacen_id',
            'tipo',
            'desde',
            'hasta',
            'incluir_anulados',
        ]);
        if ($scopedToAssignedWarehouse) {
            $filters['almacen_id'] = $user->almacen_id;
        }

        $desde = $filters['desde'] ?? '';
        $hasta = $filters['hasta'] ?? '';

        $kardex = Kardex::query()
            ->with([
                'producto:id,codigo,nombre',
                'almacen:id,codigo,nombre',
                'movimiento:id,numero,documento,estado',
            ])
            ->when($filters['producto_id'] ?? null, fn ($query, $productoId) => $query->where('producto_id', $productoId))
            ->when($filters['almacen_id'] ?? null, fn ($query, $almacenId) => $query->where('almacen_id', $almacenId))
            ->when($filters['tipo'] ?? null, fn ($query, $tipo) => $query->where('tipo', $tipo))
            ->when($desde, fn ($query, $value) => $query->whereDate('fecha', '>=', $value))
            ->when($hasta, fn ($query, $value) => $query->whereDate('fecha', '<=', $value))
            ->when(! filter_var($filters['incluir_anulados'] ?? false, FILTER_VALIDATE_BOOLEAN), function ($query) {
                $query->whereHas('movimiento', fn ($movementQuery) => $movementQuery->where('estado', '<>', 'anulado'));
            })
            ->latest('created_at')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Inventario/Kardex/Index', [
            'kardex' => $kardex,
            'filters' => [
                'producto_id' => $filters['producto_id'] ?? '',
                'almacen_id' => $filters['almacen_id'] ?? '',
                'tipo' => $filters['tipo'] ?? '',
                'desde' => $desde,
                'hasta' => $hasta,
                'incluir_anulados' => filter_var($filters['incluir_anulados'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ],
            'scope' => [
                'es_almacenero' => $scopedToAssignedWarehouse,
                'almacen_id' => $user->almacen_id ?? null,
            ],
            'productos' => Producto::query()
                ->where('activo', true)
                ->when($scopedToAssignedWarehouse && $user->almacen_id, function ($query) use ($user) {
                    $query->whereHas('stocks', fn ($stockQuery) => $stockQuery->where('almacen_id', $user->almacen_id));
                })
                ->orderBy('nombre')
                ->get(['id', 'codigo', 'nombre']),
            'almacenes' => Almacen::query()
                ->where('activo', true)
                ->when($scopedToAssignedWarehouse, fn ($query) => $query->where('id', $user->almacen_id))
                ->orderBy('nombre')
                ->get(['id', 'codigo', 'nombre']),
            'tipos' => Kardex::query()
                ->when($filters['almacen_id'] ?? null, fn ($query, $almacenId) => $query->where('almacen_id', $almacenId))
                ->select('tipo')
                ->distinct()
                ->orderBy('tipo')
                ->pluck('tipo'),
        ]);
    }
}
