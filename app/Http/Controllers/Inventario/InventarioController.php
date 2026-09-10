<?php

namespace App\Http\Controllers\Inventario;

use App\Http\Controllers\Controller;
use App\Services\AccessControlService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class InventarioController extends Controller
{
    public function index(Request $request, AccessControlService $access): Response
    {
        $user = $request->user();
        $filters = [
            'buscar' => $request->string('buscar')->toString(),
            'almacen_id' => $access->mustScopeToAssignedOperation($user) ? ($user->almacen_id ?? null) : $request->input('almacen_id'),
            'familia_id' => $request->input('familia_id'),
            'estado_stock' => $request->string('estado_stock')->toString(),
        ];

        return Inertia::render('Inventario/Inventario/Index', [
            'inventario' => $this->inventario($filters, $request),
            'almacenes' => $access->mustScopeToAssignedOperation($user) ? $this->almacenAsignado($user) : $this->almacenes(),
            'familias' => $this->familias(),
            'filters' => $filters,
            'scope' => [
                'es_almacenero' => $access->mustScopeToAssignedOperation($user),
                'almacen_id' => $user->almacen_id ?? null,
                'centro_costo_id' => $user->centro_costo_id ?? null,
            ],
            'totales' => $this->totales($filters),
        ]);
    }

    private function inventario(array $filters, Request $request): LengthAwarePaginator
    {
        $perPage = 10;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $rows = $this->inventarioRows($filters);

        return new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
    }

    private function inventarioRows(array $filters)
    {
        $stockTable = $this->stockTable();

        if (! $stockTable || ! Schema::hasTable('productos')) {
            return collect();
        }

        $qty = $this->stockQtyColumn($stockTable);
        $cost = $this->stockCostColumn($stockTable);

        $query = DB::table($stockTable.' as s')
            ->join('productos as p', 'p.id', '=', 's.producto_id')
            ->leftJoin('almacenes as a', 'a.id', '=', 's.almacen_id')
            ->leftJoin('familias as f', 'f.id', '=', 'p.familia_id')
            ->leftJoin('unidades_medida as u', 'u.id', '=', 'p.unidad_medida_id')
            ->select([
                's.almacen_id',
                'p.id as producto_id',
                'p.codigo',
                'p.nombre',
                'p.stock_minimo',
                'p.stock_maximo',
                'a.nombre as almacen',
                'f.nombre as familia',
                DB::raw("COALESCE(u.abreviatura, u.codigo, '-') as unidad"),
                DB::raw("COALESCE(s.{$qty}, 0) as existencia"),
                DB::raw($cost ? "COALESCE(s.{$cost}, 0) as costo_promedio" : '0 as costo_promedio'),
            ])
            ->when($filters['almacen_id'], fn ($q, $value) => $q->where('s.almacen_id', $value))
            ->when($filters['familia_id'], fn ($q, $value) => $q->where('p.familia_id', $value))
            ->when($filters['buscar'], function ($q, $value) {
                $q->where(function ($where) use ($value) {
                    $where->where('p.codigo', 'like', "%{$value}%")
                        ->orWhere('p.nombre', 'like', "%{$value}%")
                        ->orWhere('f.nombre', 'like', "%{$value}%")
                        ->orWhere('a.nombre', 'like', "%{$value}%");
                });
            });

        $rows = $query->orderBy('a.nombre')->orderBy('p.nombre')->get()
            ->map(function ($row) {
                $existencia = (float) $row->existencia;
                $minimo = (float) ($row->stock_minimo ?? 0);
                $costo = (float) $row->costo_promedio;

                return [
                    'almacen_id' => $row->almacen_id,
                    'producto_id' => $row->producto_id,
                    'almacen' => $row->almacen ?? '-',
                    'codigo' => $row->codigo,
                    'producto' => $row->nombre,
                    'familia' => $row->familia ?? '-',
                    'unidad' => $row->unidad ?? '-',
                    'existencia' => $existencia,
                    'stock_minimo' => $minimo,
                    'stock_maximo' => $row->stock_maximo !== null ? (float) $row->stock_maximo : null,
                    'costo_promedio' => $costo,
                    'valor' => $existencia * $costo,
                    'estado_stock' => $existencia <= 0 ? 'sin_stock' : ($minimo > 0 && $existencia <= $minimo ? 'bajo' : 'normal'),
                ];
            });

        if ($filters['estado_stock']) {
            $rows = $rows->where('estado_stock', $filters['estado_stock'])->values();
        }

        return $rows->values();
    }

    private function totales(array $filters): array
    {
        $rows = $this->inventarioRows($filters);

        return [
            'productos' => $rows->count(),
            'existencia' => $rows->sum('existencia'),
            'valor' => $rows->sum('valor'),
            'stock_bajo' => $rows->where('estado_stock', 'bajo')->count(),
            'sin_stock' => $rows->where('estado_stock', 'sin_stock')->count(),
        ];
    }

    private function almacenes(): array
    {
        if (! Schema::hasTable('almacenes')) {
            return [];
        }

        return DB::table('almacenes')
            ->select('id', 'nombre')
            ->orderBy('nombre')
            ->get()
            ->map(fn ($row) => ['id' => $row->id, 'nombre' => $row->nombre])
            ->all();
    }

    private function almacenAsignado($user): array
    {
        if (! Schema::hasTable('almacenes') || ! ($user->almacen_id ?? null)) {
            return [];
        }

        $row = DB::table('almacenes')->select('id', 'nombre')->where('id', $user->almacen_id)->first();

        return $row ? [['id' => $row->id, 'nombre' => $row->nombre]] : [];
    }

    private function familias(): array
    {
        if (! Schema::hasTable('familias')) {
            return [];
        }

        return DB::table('familias')
            ->select('id', 'nombre')
            ->orderBy('nombre')
            ->get()
            ->map(fn ($row) => ['id' => $row->id, 'nombre' => $row->nombre])
            ->all();
    }

    private function stockTable(): ?string
    {
        foreach (['stock_almacen', 'stocks', 'inventarios', 'inventario_stocks'] as $table) {
            if (Schema::hasTable($table)) {
                return $table;
            }
        }

        return null;
    }

    private function stockQtyColumn(string $table): string
    {
        foreach (['cantidad', 'existencia', 'stock_actual', 'stock'] as $column) {
            if (Schema::hasColumn($table, $column)) {
                return $column;
            }
        }

        return 'cantidad';
    }

    private function stockCostColumn(string $table): ?string
    {
        foreach (['costo_promedio', 'costo_unitario', 'costo'] as $column) {
            if (Schema::hasColumn($table, $column)) {
                return $column;
            }
        }

        return null;
    }
}
