<?php

namespace App\Http\Controllers\Operaciones;

use App\Http\Controllers\Controller;
use App\Models\Almacen;
use App\Models\CentroCosto;
use App\Models\Prestamo;
use App\Models\Producto;
use App\Models\Trabajador;
use App\Services\MovimientoService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PrestamoController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Operaciones/Prestamos/Index', [
            'prestamos' => $this->prestamos(),
            'productos' => $this->productosPrestables(),
            'trabajadores' => $this->trabajadores(),
            'almacenes' => $this->almacenes(),
            'centros' => $this->centros(),
        ]);
    }

    public function store(Request $request, MovimientoService $movimientos): RedirectResponse
    {
        $data = $request->validate([
            'trabajador_id' => ['required', 'exists:trabajadores,id'],
            'almacen_id' => ['required', 'exists:almacenes,id'],
            'centro_costo_id' => ['nullable', 'exists:centros_costos,id'],
            'fecha_prestamo' => ['nullable', 'date'],
            'fecha' => ['nullable', 'date'],
            'fecha_devolucion_programada' => ['nullable', 'date'],
            'fecha_devolucion_estimada' => ['nullable', 'date'],
            'motivo' => ['nullable', 'string'],
            'observaciones' => ['nullable', 'string'],
            'detalles' => ['required', 'array', 'min:1'],
            'detalles.*.producto_id' => ['required', 'exists:productos,id'],
            'detalles.*.cantidad' => ['required', 'numeric', 'min:0.0001'],
            'detalles.*.observaciones' => ['nullable', 'string'],
        ]);

        $fecha = $data['fecha_prestamo'] ?? $data['fecha'] ?? now()->toDateString();
        $codigo = $this->generarNumero();
        $fechaDevolucionProgramada = $data['fecha_devolucion_programada'] ?? $data['fecha_devolucion_estimada'] ?? null;

        DB::transaction(function () use ($data, $request, $movimientos, $fecha, $codigo, $fechaDevolucionProgramada) {
            $movimientoSalidaId = $movimientos->registrarPrestamo([
                'numero' => $codigo,
                'almacen_origen_id' => $data['almacen_id'],
                'centro_costo_id' => $data['centro_costo_id'] ?? null,
                'fecha' => $fecha,
                'documento' => $codigo,
                'observaciones' => $data['motivo'] ?? 'Prestamo de herramienta/equipo',
                'items' => $data['detalles'],
            ], $request->user()->id);

            $prestamoId = DB::table('prestamos')->insertGetId($this->onlyExistingColumns('prestamos', [
                'codigo' => $codigo,
                'numero' => $codigo,
                'trabajador_id' => $data['trabajador_id'],
                'almacen_id' => $data['almacen_id'],
                'centro_costo_id' => $data['centro_costo_id'] ?? null,
                'movimiento_salida_id' => $movimientoSalidaId,
                'fecha' => $fecha,
                'fecha_prestamo' => $fecha,
                'fecha_devolucion_programada' => $fechaDevolucionProgramada,
                'fecha_devolucion_estimada' => $fechaDevolucionProgramada,
                'motivo' => $data['motivo'] ?? null,
                'observaciones' => $data['observaciones'] ?? null,
                'estado' => 'prestado',
                'usuario_id' => $request->user()->id,
                'user_id' => $request->user()->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]));

            foreach ($data['detalles'] as $detalle) {
                DB::table('prestamo_detalles')->insert($this->onlyExistingColumns('prestamo_detalles', [
                    'prestamo_id' => $prestamoId,
                    'producto_id' => $detalle['producto_id'],
                    'cantidad' => $detalle['cantidad'],
                    'cantidad_prestada' => $detalle['cantidad'],
                    'cantidad_devuelta' => 0,
                    'observaciones' => $detalle['observaciones'] ?? null,
                    'estado' => 'prestado',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        });

        return back()->with('success', 'Prestamo registrado y stock descontado.');
    }

    public function devolver(Request $request, Prestamo $prestamo, MovimientoService $movimientos): RedirectResponse
    {
        $data = $request->validate([
            'fecha_devolucion' => ['nullable', 'date'],
            'motivo' => ['nullable', 'string'],
            'motivo_devolucion' => ['nullable', 'string'],
            'observaciones' => ['nullable', 'string'],
            'detalles' => ['required', 'array', 'min:1'],
            'detalles.*.id' => ['required'],
            'detalles.*.cantidad' => ['nullable', 'numeric', 'min:0.0001'],
            'detalles.*.cantidad_devuelta' => ['nullable', 'numeric', 'min:0.0001'],
        ]);

        $fecha = $data['fecha_devolucion'] ?? now()->toDateString();

        DB::transaction(function () use ($data, $request, $prestamo, $movimientos, $fecha) {
            $items = [];

            foreach ($data['detalles'] as $linea) {
                $detalle = DB::table('prestamo_detalles')
                    ->where('id', $linea['id'])
                    ->where('prestamo_id', $prestamo->id)
                    ->lockForUpdate()
                    ->first();

                if (! $detalle) {
                    throw ValidationException::withMessages([
                        'detalles' => 'Detalle de prestamo no encontrado.',
                    ]);
                }

                $prestada = (float) ($detalle->cantidad_prestada ?? $detalle->cantidad ?? 0);
                $devuelta = (float) ($detalle->cantidad_devuelta ?? 0);
                $cantidad = (float) ($linea['cantidad'] ?? $linea['cantidad_devuelta'] ?? 0);

                if ($cantidad <= 0) {
                    throw ValidationException::withMessages([
                        'cantidad' => 'La cantidad a devolver debe ser mayor a cero.',
                    ]);
                }
                $pendiente = max($prestada - $devuelta, 0);

                if ($cantidad > $pendiente) {
                    throw ValidationException::withMessages([
                        'cantidad' => 'No puede devolver mas de lo pendiente.',
                    ]);
                }

                $nuevaDevuelta = $devuelta + $cantidad;

                DB::table('prestamo_detalles')->where('id', $detalle->id)->update($this->onlyExistingColumns('prestamo_detalles', [
                    'cantidad_devuelta' => $nuevaDevuelta,
                    'estado' => $nuevaDevuelta >= $prestada ? 'devuelto' : 'parcial',
                    'fecha_devolucion' => $fecha,
                    'updated_at' => now(),
                ]));

                $items[] = [
                    'producto_id' => $detalle->producto_id,
                    'cantidad' => $cantidad,
                    'costo_unitario' => 0,
                    'observaciones' => $data['observaciones'] ?? null,
                ];
            }

            $movimientoEntradaId = $movimientos->registrarDevolucionPrestamo([
                'almacen_destino_id' => $prestamo->almacen_id,
                'centro_costo_id' => $prestamo->centro_costo_id ?? null,
                'fecha' => $fecha,
                'documento' => $prestamo->codigo ?? $prestamo->numero,
                'observaciones' => $data['motivo'] ?? $data['motivo_devolucion'] ?? 'Devolucion de prestamo',
                'items' => $items,
            ], $request->user()->id);

            $this->actualizarEstadoPrestamo($prestamo->id, $fecha, $data['motivo'] ?? $data['motivo_devolucion'] ?? null, $movimientoEntradaId);
        });

        return back()->with('success', 'Devolucion registrada y stock repuesto.');
    }

    public function registrarDevolucion(Request $request, Prestamo $prestamo, MovimientoService $movimientos): RedirectResponse
    {
        return $this->devolver($request, $prestamo, $movimientos);
    }

    public function receive(Request $request, Prestamo $prestamo, MovimientoService $movimientos): RedirectResponse
    {
        return $this->devolver($request, $prestamo, $movimientos);
    }

    public function pdf(Prestamo $prestamo)
    {
        $prestamo = $this->prestamoPdf($prestamo->id);

        return Pdf::loadView('pdf.prestamo', compact('prestamo'))
            ->setPaper('a4', 'portrait')
            ->stream(($prestamo->numero ?? 'prestamo').'.pdf');
    }

    private function prestamos()
    {
        return Prestamo::query()
            ->with(['trabajador', 'almacen', 'centroCosto', 'detalles.producto.unidad'])
            ->latest('id')
            ->paginate(25)
            ->withQueryString();
    }

    private function productosPrestables()
    {
        return Producto::query()
            ->with(['unidad', 'familia'])
            ->whereHas('familia', function ($query) {
                $query->where(function ($familia) {
                    $familia->where('nombre', 'like', '%herramient%')
                        ->orWhere('nombre', 'like', '%equipo%')
                        ->orWhere('codigo', 'like', '%HERR%')
                        ->orWhere('codigo', 'like', '%EQUI%');
                });
            })
            ->when(Schema::hasColumn('productos', 'activo'), fn ($query) => $query->where('activo', true))
            ->orderBy('nombre')
            ->get();
    }

    private function trabajadores()
    {
        return Trabajador::query()
            ->when(Schema::hasColumn('trabajadores', 'activo'), fn ($query) => $query->where('activo', true))
            ->orderBy(Schema::hasColumn('trabajadores', 'nombre') ? 'nombre' : 'id')
            ->get()
            ->values();
    }

    private function almacenes()
    {
        return Almacen::query()
            ->when(Schema::hasColumn('almacenes', 'estado'), fn ($query) => $query->where('estado', 'activo'))
            ->when(Schema::hasColumn('almacenes', 'activo'), fn ($query) => $query->where('activo', true))
            ->orderBy('nombre')
            ->get();
    }

    private function centros()
    {
        return CentroCosto::query()
            ->when(Schema::hasColumn('centros_costos', 'estado'), fn ($query) => $query->where('estado', 'activo'))
            ->when(Schema::hasColumn('centros_costos', 'activo'), fn ($query) => $query->where('activo', true))
            ->orderBy('nombre')
            ->get();
    }

    private function actualizarEstadoPrestamo(int $prestamoId, string $fecha, ?string $motivo = null, ?int $movimientoEntradaId = null): void
    {
        $detalles = DB::table('prestamo_detalles')->where('prestamo_id', $prestamoId)->get();
        $totalPrestado = $detalles->sum(fn ($detalle) => (float) ($detalle->cantidad_prestada ?? $detalle->cantidad ?? 0));
        $totalDevuelto = $detalles->sum(fn ($detalle) => (float) ($detalle->cantidad_devuelta ?? 0));

        $estado = $totalDevuelto <= 0
            ? 'prestado'
            : ($totalDevuelto >= $totalPrestado ? 'devuelto' : 'parcial');

        DB::table('prestamos')->where('id', $prestamoId)->update($this->onlyExistingColumns('prestamos', [
            'estado' => $estado,
            'fecha_devolucion' => $estado === 'devuelto' ? $fecha : null,
            'movimiento_entrada_id' => $movimientoEntradaId,
            'motivo_devolucion' => $motivo,
            'updated_at' => now(),
        ]));
    }

    private function generarNumero(): string
    {
        $year = now()->format('Ym');
        $count = DB::table('prestamos')
            ->where('numero', 'like', "PRE-{$year}-%")
            ->lockForUpdate()
            ->count() + 1;

        return sprintf('PRE-%s-%06d', $year, $count);
    }

    private function prestamoPdf(int $id)
    {
        return Prestamo::query()
            ->with(['trabajador', 'almacen', 'centroCosto', 'detalles.producto.unidad'])
            ->findOrFail($id);
    }

    private function onlyExistingColumns(string $table, array $payload): array
    {
        if (! Schema::hasTable($table)) {
            return $payload;
        }

        return collect($payload)
            ->filter(fn ($value, $column) => Schema::hasColumn($table, $column))
            ->all();
    }
}
