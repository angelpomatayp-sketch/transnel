<?php

namespace App\Http\Controllers\Operaciones;

use App\Http\Controllers\Controller;
use App\Models\Almacen;
use App\Models\CentroCosto;
use App\Models\EppAsignacion;
use App\Models\Producto;
use App\Models\Trabajador;
use App\Services\MovimientoService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class EppController extends Controller
{
    public function index(Request $request): Response
    {
        $this->ensureEppAsignacionesTable();

        return Inertia::render('Operaciones/Epps/Index', [
            'asignaciones' => Schema::hasTable('epp_asignaciones')
                ? EppAsignacion::query()
                    ->with(['trabajador', 'producto.unidad', 'producto.familia', 'almacen', 'centroCosto'])
                    ->latest('id')
                    ->get()
                    ->map(fn (EppAsignacion $asignacion) => $this->presentarAsignacion($asignacion))
                    ->values()
                : [],
            'productos' => $this->productosEpp(),
            'trabajadores' => $this->trabajadoresParaSelector(),
            'almacenes' => Almacen::query()
                ->when(Schema::hasColumn('almacenes', 'estado'), fn ($query) => $query->where('estado', 'activo'))
                ->when(Schema::hasColumn('almacenes', 'activo'), fn ($query) => $query->where('activo', true))
                ->orderBy('nombre')
                ->get(),
            'centros' => CentroCosto::query()
                ->when(Schema::hasColumn('centros_costos', 'estado'), fn ($query) => $query->where('estado', 'activo'))
                ->when(Schema::hasColumn('centros_costos', 'activo'), fn ($query) => $query->where('activo', true))
                ->orderBy('nombre')
                ->get(),
        ]);
    }

    public function store(Request $request, MovimientoService $movimientoService): RedirectResponse
    {
        $this->ensureEppAsignacionesTable();

        $data = $request->validate([
            'trabajador_id' => ['required', 'exists:trabajadores,id'],
            'producto_id' => ['required', 'exists:productos,id'],
            'almacen_id' => ['required', 'exists:almacenes,id'],
            'centro_costo_id' => ['nullable', 'exists:centros_costos,id'],
            'cantidad' => ['required', 'numeric', 'min:0.0001'],
            'talla' => ['nullable', 'string', 'max:50'],
            'fecha_entrega' => ['required', 'date'],
            'observaciones' => ['nullable', 'string'],
        ]);

        $producto = Producto::query()->with('familia')->findOrFail($data['producto_id']);

        if (! $this->productoEsEpp($producto)) {
            return back()->with('error', 'El producto seleccionado no pertenece a una familia EPP.');
        }

        DB::transaction(function () use ($data, $producto, $request, $movimientoService) {
            $codigo = $this->generarCodigo();
            $movimientoId = $movimientoService->registrar([
                'tipo' => 'salida',
                'almacen_origen_id' => $data['almacen_id'],
                'centro_costo_id' => $data['centro_costo_id'] ?? null,
                'fecha' => $data['fecha_entrega'],
                'documento' => $codigo,
                'observaciones' => 'Entrega de EPP',
                'detalles' => [[
                    'producto_id' => $data['producto_id'],
                    'cantidad' => $data['cantidad'],
                ]],
            ], $request->user()->id);

            EppAsignacion::query()->create([
                'codigo' => $codigo,
                'trabajador_id' => $data['trabajador_id'],
                'producto_id' => $data['producto_id'],
                'almacen_id' => $data['almacen_id'],
                'centro_costo_id' => $data['centro_costo_id'] ?? null,
                'movimiento_id' => $movimientoId,
                'cantidad' => $data['cantidad'],
                'talla' => $data['talla'] ?? null,
                'fecha_entrega' => $data['fecha_entrega'],
                'fecha_vencimiento' => $this->fechaVencimiento($producto, $data['fecha_entrega']),
                'estado' => 'entregado',
                'observaciones' => $data['observaciones'] ?? null,
            ]);
        });

        return back()->with('success', 'EPP asignado y stock actualizado.');
    }

    public function devolver(Request $request, EppAsignacion $asignacion): RedirectResponse
    {
        if ($asignacion->estado !== 'entregado') {
            return back()->with('error', 'Solo se pueden devolver asignaciones entregadas.');
        }

        $data = $request->validate([
            'motivo_devolucion' => ['required', 'string', 'max:500'],
            'estado' => ['nullable', Rule::in(['devuelto', 'baja'])],
        ]);

        $estado = $data['estado'] ?? 'devuelto';

        $asignacion->update([
            'estado' => $estado,
            'fecha_devolucion' => now(),
            'motivo_devolucion' => $data['motivo_devolucion'],
        ]);

        return back()->with('success', $estado === 'devuelto' ? 'EPP marcado como devuelto.' : 'EPP marcado como baja.');
    }

    public function renovar(Request $request, EppAsignacion $asignacion, MovimientoService $movimientoService): RedirectResponse
    {
        if ($asignacion->estado !== 'entregado') {
            return back()->with('error', 'Solo se pueden renovar EPPs vigentes.');
        }

        $data = $request->validate([
            'producto_id' => ['nullable', 'exists:productos,id'],
            'almacen_id' => ['nullable', 'exists:almacenes,id'],
            'centro_costo_id' => ['nullable', 'exists:centros_costos,id'],
            'cantidad' => ['nullable', 'numeric', 'min:0.0001'],
            'talla' => ['nullable', 'string', 'max:50'],
            'fecha_entrega' => ['nullable', 'date'],
            'observaciones' => ['nullable', 'string'],
            'motivo_devolucion' => ['nullable', 'string', 'max:500'],
        ]);

        $producto = Producto::query()
            ->with('familia')
            ->findOrFail($data['producto_id'] ?? $asignacion->producto_id);

        if (! $this->productoEsEpp($producto)) {
            return back()->with('error', 'El producto seleccionado no pertenece a una familia EPP.');
        }

        DB::transaction(function () use ($asignacion, $data, $producto, $request, $movimientoService) {
            $fechaEntrega = $data['fecha_entrega'] ?? now()->toDateString();
            $codigo = $this->generarCodigo();
            $cantidad = $data['cantidad'] ?? $asignacion->cantidad;
            $almacenId = $data['almacen_id'] ?? $asignacion->almacen_id;

            $movimientoId = $movimientoService->registrar([
                'tipo' => 'salida',
                'almacen_origen_id' => $almacenId,
                'centro_costo_id' => $data['centro_costo_id'] ?? $asignacion->centro_costo_id,
                'fecha' => $fechaEntrega,
                'documento' => $codigo,
                'observaciones' => 'Renovacion de EPP: '.$asignacion->codigo,
                'detalles' => [[
                    'producto_id' => $producto->id,
                    'cantidad' => $cantidad,
                ]],
            ], $request->user()->id);

            $asignacion->update([
                'estado' => 'renovado',
                'fecha_devolucion' => now(),
                'motivo_devolucion' => $data['motivo_devolucion'] ?? 'Renovacion de EPP',
            ]);

            EppAsignacion::query()->create([
                'codigo' => $codigo,
                'trabajador_id' => $asignacion->trabajador_id,
                'producto_id' => $producto->id,
                'almacen_id' => $almacenId,
                'centro_costo_id' => $data['centro_costo_id'] ?? $asignacion->centro_costo_id,
                'movimiento_id' => $movimientoId,
                'cantidad' => $cantidad,
                'talla' => $data['talla'] ?? $asignacion->talla,
                'fecha_entrega' => $fechaEntrega,
                'fecha_vencimiento' => $this->fechaVencimiento($producto, $fechaEntrega),
                'estado' => 'entregado',
                'observaciones' => $data['observaciones'] ?? 'Renovacion de '.$asignacion->codigo,
            ]);
        });

        return back()->with('success', 'EPP renovado y stock actualizado.');
    }

    private function productosEpp()
    {
        $query = Producto::query()->with(['unidad', 'familia'])->orderBy('nombre');

        if (Schema::hasColumn('familias', 'es_epp')) {
            $query->whereHas('familia', fn ($family) => $family->where('es_epp', true));
        } elseif (Schema::hasColumn('productos', 'es_epp')) {
            $query->where('es_epp', true);
        }

        return $query->get();
    }

    private function productoEsEpp(Producto $producto): bool
    {
        if ($producto->familia && isset($producto->familia->es_epp)) {
            return (bool) $producto->familia->es_epp;
        }

        if (isset($producto->es_epp)) {
            return (bool) $producto->es_epp;
        }

        return true;
    }

    private function fechaVencimiento(Producto $producto, string $fechaEntrega): ?string
    {
        $dias = $producto->vida_util_dias ?? null;

        if (! $dias || (int) $dias <= 0) {
            $dias = 365;
        }

        return Carbon::parse($fechaEntrega)->addDays((int) $dias)->toDateString();
    }

    private function generarCodigo(): string
    {
        $this->ensureEppAsignacionesTable();

        $year = now()->format('Ym');
        $count = EppAsignacion::query()
            ->where('codigo', 'like', "EPP-{$year}-%")
            ->lockForUpdate()
            ->count() + 1;

        return sprintf('EPP-%s-%06d', $year, $count);
    }

    private function ensureEppAsignacionesTable(): void
    {
        if (Schema::hasTable('epp_asignaciones')) {
            return;
        }

        Schema::create('epp_asignaciones', function ($table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->unsignedBigInteger('trabajador_id');
            $table->unsignedBigInteger('producto_id');
            $table->unsignedBigInteger('almacen_id');
            $table->unsignedBigInteger('centro_costo_id')->nullable();
            $table->unsignedBigInteger('movimiento_id')->nullable();
            $table->decimal('cantidad', 12, 4)->default(1);
            $table->string('talla', 50)->nullable();
            $table->date('fecha_entrega');
            $table->date('fecha_vencimiento')->nullable();
            $table->string('estado', 30)->default('entregado');
            $table->text('observaciones')->nullable();
            $table->timestamp('fecha_devolucion')->nullable();
            $table->text('motivo_devolucion')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('trabajador_id');
            $table->index('producto_id');
            $table->index('almacen_id');
            $table->index('estado');
        });
    }

    private function trabajadoresParaSelector()
    {
        if (! Schema::hasTable('trabajadores')) {
            return [];
        }

        return Trabajador::query()
            ->when(Schema::hasColumn('trabajadores', 'estado'), fn ($query) => $query->where('estado', 'activo'))
            ->when(Schema::hasColumn('trabajadores', 'activo'), fn ($query) => $query->where('activo', true))
            ->orderBy(Schema::hasColumn('trabajadores', 'nombre') ? 'nombre' : 'id')
            ->get()
            ->map(fn (Trabajador $trabajador) => [
                'id' => $trabajador->id,
                'dni' => $trabajador->dni ?? '',
                'nombre' => trim($trabajador->nombre ?? trim(($trabajador->nombres ?? '').' '.($trabajador->apellidos ?? ''))) ?: 'Trabajador',
                'cargo' => $trabajador->cargo ?? '',
            ])
            ->values();
    }

    private function presentarAsignacion(EppAsignacion $asignacion): array
    {
        $trabajador = $asignacion->trabajador;
        $nombreTrabajador = trim($trabajador->nombre ?? trim(($trabajador->nombres ?? '').' '.($trabajador->apellidos ?? ''))) ?: '-';

        return [
            ...$asignacion->toArray(),
            'trabajador_nombre' => $nombreTrabajador,
            'trabajador_dni' => $trabajador->dni ?? '',
            'producto_nombre' => $asignacion->producto->nombre ?? '-',
            'producto_codigo' => $asignacion->producto->codigo ?? '-',
            'unidad' => $asignacion->producto->unidad->abreviatura ?? $asignacion->producto->unidad->nombre ?? '',
            'familia' => $asignacion->producto->familia->nombre ?? '',
            'almacen_nombre' => $asignacion->almacen->nombre ?? '-',
            'centro_nombre' => $asignacion->centroCosto->nombre ?? '-',
        ];
    }
}
