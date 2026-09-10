<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class AuditoriaInventarioController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'buscar' => $request->string('buscar')->toString(),
            'producto_id' => $request->input('producto_id'),
            'almacen_id' => $request->input('almacen_id'),
            'tipo' => $request->string('tipo')->toString(),
            'accion' => $request->string('accion')->toString(),
            'desde' => $request->input('desde'),
            'hasta' => $request->input('hasta'),
        ];

        return Inertia::render('Reportes/AuditoriaInventario/Index', [
            'auditorias' => $this->auditorias($filters),
            'productos' => $this->productos(),
            'almacenes' => $this->almacenes(),
            'filters' => $filters,
            'tipos' => $this->opciones('tipo'),
            'acciones' => $this->opciones('accion'),
        ]);
    }

    private function auditorias(array $filters): array
    {
        if (! Schema::hasTable('inventario_auditorias')) {
            return [];
        }

        $query = DB::table('inventario_auditorias as ia')
            ->leftJoin('productos as p', 'p.id', '=', 'ia.producto_id')
            ->leftJoin('almacenes as a', 'a.id', '=', 'ia.almacen_id')
            ->leftJoin('users as u', 'u.id', '=', 'ia.user_id')
            ->select([
                'ia.id',
                'ia.created_at',
                'ia.accion',
                'ia.tipo',
                'ia.cantidad',
                'ia.stock_antes',
                'ia.stock_despues',
                'ia.costo_promedio',
                'ia.documento',
                'ia.motivo',
                'p.codigo as producto_codigo',
                'p.nombre as producto_nombre',
                'a.nombre as almacen_nombre',
                'u.name as usuario_nombre',
            ])
            ->when($filters['producto_id'], fn ($q, $value) => $q->where('ia.producto_id', $value))
            ->when($filters['almacen_id'], fn ($q, $value) => $q->where('ia.almacen_id', $value))
            ->when($filters['tipo'], fn ($q, $value) => $q->where('ia.tipo', $value))
            ->when($filters['accion'], fn ($q, $value) => $q->where('ia.accion', $value))
            ->when($filters['desde'], fn ($q, $value) => $q->whereDate('ia.created_at', '>=', $value))
            ->when($filters['hasta'], fn ($q, $value) => $q->whereDate('ia.created_at', '<=', $value))
            ->when($filters['buscar'], function ($q, $value) {
                $q->where(function ($where) use ($value) {
                    $where->where('ia.documento', 'like', "%{$value}%")
                        ->orWhere('ia.motivo', 'like', "%{$value}%")
                        ->orWhere('p.codigo', 'like', "%{$value}%")
                        ->orWhere('p.nombre', 'like', "%{$value}%")
                        ->orWhere('a.nombre', 'like', "%{$value}%")
                        ->orWhere('u.name', 'like', "%{$value}%");
                });
            })
            ->orderByDesc('ia.id')
            ->limit(500);

        return $query->get()->map(fn ($row) => [
            'id' => $row->id,
            'fecha' => $row->created_at ? \Carbon\Carbon::parse($row->created_at)->format('Y-m-d H:i') : '-',
            'accion' => $row->accion,
            'tipo' => $row->tipo,
            'producto' => trim(($row->producto_codigo ? "{$row->producto_codigo} - " : '').($row->producto_nombre ?? '-')),
            'almacen' => $row->almacen_nombre ?? '-',
            'usuario' => $row->usuario_nombre ?? '-',
            'cantidad' => (float) $row->cantidad,
            'stock_antes' => $row->stock_antes !== null ? (float) $row->stock_antes : null,
            'stock_despues' => $row->stock_despues !== null ? (float) $row->stock_despues : null,
            'costo_promedio' => $row->costo_promedio !== null ? (float) $row->costo_promedio : null,
            'documento' => $row->documento ?? '-',
            'motivo' => $row->motivo ?? '-',
        ])->values()->all();
    }

    private function productos(): array
    {
        if (! Schema::hasTable('productos')) {
            return [];
        }

        return DB::table('productos')
            ->select('productos.id', 'productos.codigo', 'productos.nombre')
            ->orderBy('productos.nombre')
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'nombre' => trim(($row->codigo ? "{$row->codigo} - " : '').$row->nombre),
            ])
            ->all();
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
            ->map(fn ($row) => [
                'id' => $row->id,
                'nombre' => $row->nombre,
            ])
            ->all();
    }

    private function opciones(string $column): array
    {
        if (! Schema::hasTable('inventario_auditorias') || ! Schema::hasColumn('inventario_auditorias', $column)) {
            return [];
        }

        return DB::table('inventario_auditorias')
            ->whereNotNull($column)
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->values()
            ->all();
    }
}
