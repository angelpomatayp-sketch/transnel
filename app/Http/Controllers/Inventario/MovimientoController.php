<?php

namespace App\Http\Controllers\Inventario;

use App\Http\Controllers\Controller;
use App\Models\Almacen;
use App\Models\CentroCosto;
use App\Models\Movimiento;
use App\Models\Producto;
use App\Models\StockAlmacen;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use App\Services\InventarioService;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MovimientoController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['buscar', 'tipo', 'estado', 'desde', 'hasta']);

        return Inertia::render('Inventario/Movimientos/Index', [
            'movimientos' => Movimiento::query()
                ->with(['almacenOrigen:id,codigo,nombre', 'almacenDestino:id,codigo,nombre', 'usuario:id,name', 'detalles.producto:id,codigo,nombre'])
                ->when($filters['buscar'] ?? null, function ($query, $buscar) {
                    $query->where(function ($subquery) use ($buscar) {
                        $subquery
                            ->where('numero', 'like', "%{$buscar}%")
                            ->orWhere('documento', 'like', "%{$buscar}%");
                    });
                })
                ->when($filters['tipo'] ?? null, fn ($query, $tipo) => $query->where('tipo', $tipo))
                ->when($filters['estado'] ?? null, fn ($query, $estado) => $query->where('estado', $estado))
                ->when($filters['desde'] ?? null, fn ($query, $desde) => $query->whereDate('fecha', '>=', $desde))
                ->when($filters['hasta'] ?? null, fn ($query, $hasta) => $query->whereDate('fecha', '<=', $hasta))
                ->latest('created_at')
                ->latest('id')
                ->paginate(25)
                ->withQueryString(),
            'filters' => [
                'buscar' => $filters['buscar'] ?? '',
                'tipo' => $filters['tipo'] ?? '',
                'estado' => $filters['estado'] ?? '',
                'desde' => $filters['desde'] ?? '',
                'hasta' => $filters['hasta'] ?? '',
            ],
            'productos' => Producto::query()
                ->where('activo', true)
                ->orderBy('nombre')
                ->get(['id', 'codigo', 'nombre', 'costo_referencial']),
            'almacenes' => Almacen::query()
                ->where('activo', true)
                ->orderBy('nombre')
                ->get(['id', 'codigo', 'nombre']),
            'centros' => CentroCosto::query()
                ->where('activo', true)
                ->orderBy('nombre')
                ->get(['id', 'codigo', 'nombre']),
        ]);
    }

    public function store(Request $request, InventarioService $inventario): RedirectResponse
    {
        $data = $request->validate([
            'tipo' => ['required', 'in:ENTRADA,SALIDA,TRANSFERENCIA,AJUSTE_ENTRADA,AJUSTE_SALIDA'],
            'almacen_origen_id' => ['nullable', 'exists:almacenes,id', 'required_if:tipo,SALIDA,TRANSFERENCIA,AJUSTE_SALIDA'],
            'almacen_destino_id' => ['nullable', 'exists:almacenes,id', 'required_if:tipo,ENTRADA,TRANSFERENCIA,AJUSTE_ENTRADA'],
            'centro_costo_id' => ['nullable', 'exists:centros_costos,id'],
            'fecha' => ['required', 'date'],
            'documento' => ['nullable', 'string', 'max:255'],
            'observaciones' => ['nullable', 'string'],
            'detalles' => ['required', 'array', 'min:1'],
            'detalles.*.producto_id' => ['required', 'exists:productos,id'],
            'detalles.*.cantidad' => ['required', 'numeric', 'min:0.0001'],
            'detalles.*.costo_unitario' => ['nullable', 'numeric', 'min:0'],
            'detalles.*.lote' => ['nullable', 'string', 'max:255'],
            'detalles.*.vencimiento' => ['nullable', 'date'],
        ]);

        $data['usuario_id'] = $request->user()->id;
        $inventario->registrarMovimiento($data);

        return back()->with('success', 'Movimiento confirmado y kardex actualizado.');
    }

    public function adjust(Request $request, InventarioService $inventario): RedirectResponse
    {
        $data = $request->validate([
            'almacen_id' => ['required', 'exists:almacenes,id'],
            'producto_id' => ['required', 'exists:productos,id'],
            'stock_fisico' => ['required', 'numeric', 'min:0'],
            'costo_unitario' => ['nullable', 'numeric', 'min:0'],
            'fecha' => ['required', 'date'],
            'motivo' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $producto = Producto::query()->findOrFail($data['producto_id']);
        $stock = StockAlmacen::query()
            ->where('producto_id', $producto->id)
            ->where('almacen_id', $data['almacen_id'])
            ->first();

        $stockSistema = round((float) ($stock?->stock_actual ?? 0), 4);
        $stockFisico = round((float) $data['stock_fisico'], 4);
        $diferencia = round($stockFisico - $stockSistema, 4);

        if ($diferencia === 0.0) {
            throw ValidationException::withMessages([
                'stock_fisico' => 'No existe diferencia entre el stock fisico y el stock del sistema.',
            ]);
        }

        $tipo = $diferencia > 0 ? 'AJUSTE_ENTRADA' : 'AJUSTE_SALIDA';
        $cantidad = abs($diferencia);
        $costoUnitario = $diferencia > 0
            ? round((float) ($data['costo_unitario'] ?? $stock?->costo_promedio ?? 0), 4)
            : 0;

        $inventario->registrarMovimiento([
            'tipo' => $tipo,
            'almacen_origen_id' => $diferencia < 0 ? $data['almacen_id'] : null,
            'almacen_destino_id' => $diferencia > 0 ? $data['almacen_id'] : null,
            'usuario_id' => $request->user()->id,
            'fecha' => $data['fecha'],
            'documento' => 'REAJUSTE',
            'observaciones' => sprintf(
                'Reajuste de inventario. Stock sistema: %.2f. Stock fisico: %.2f. Motivo: %s',
                $stockSistema,
                $stockFisico,
                $data['motivo'],
            ),
            'detalles' => [[
                'producto_id' => $producto->id,
                'cantidad' => $cantidad,
                'costo_unitario' => $costoUnitario,
            ]],
        ]);

        return back()->with('success', 'Reajuste de inventario registrado.');
    }

    public function cancel(Request $request, Movimiento $movimiento, InventarioService $inventario): RedirectResponse
    {
        $data = $request->validate([
            'motivo' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $inventario->anularMovimiento($movimiento, $request->user(), $data['motivo']);

        return back()->with('success', 'Movimiento anulado y stock revertido.');
    }

    public function template(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['codigo', 'nombre', 'cantidad', 'costo_unitario', 'lote', 'vencimiento'],
            ['EPP-CAS-001', '', 10, 35, 'L-001', '2026-12-31'],
            ['', 'Guantes de cuero reforzado', 5, 18, '', ''],
        ]);

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, 'plantilla_movimiento_productos.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function importItems(Request $request): JsonResponse
    {
        $request->validate([
            'archivo' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
            'tipo' => ['required', 'in:ENTRADA,SALIDA'],
        ]);

        $spreadsheet = IOFactory::load($request->file('archivo')->getRealPath());
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
        $items = [];
        $errors = [];

        foreach (array_slice($rows, 1) as $index => $row) {
            $rowNumber = $index + 2;
            $codigo = trim((string) ($row['A'] ?? ''));
            $nombre = trim((string) ($row['B'] ?? ''));

            if ($codigo === '' && $nombre === '') {
                continue;
            }

            $producto = Producto::query()
                ->where('activo', true)
                ->when($codigo !== '', fn ($query) => $query->where('codigo', $codigo))
                ->when($codigo === '' && $nombre !== '', fn ($query) => $query->where('nombre', $nombre))
                ->first();

            if (! $producto) {
                $errors[] = "Fila {$rowNumber}: producto no encontrado.";
                continue;
            }

            $cantidad = (float) ($row['C'] ?? 0);

            if ($cantidad <= 0) {
                $errors[] = "Fila {$rowNumber}: cantidad invalida.";
                continue;
            }

            $items[] = [
                'producto_id' => $producto->id,
                'cantidad' => (string) $cantidad,
                'costo_unitario' => $request->string('tipo')->toString() === 'ENTRADA'
                    ? (string) (float) ($row['D'] ?? 0)
                    : '',
                'lote' => blank($row['E'] ?? null) ? '' : trim((string) $row['E']),
                'vencimiento' => blank($row['F'] ?? null) ? '' : trim((string) $row['F']),
            ];
        }

        return response()->json([
            'items' => $items,
            'errors' => $errors,
        ]);
    }

}
