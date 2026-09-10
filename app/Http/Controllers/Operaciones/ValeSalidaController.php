<?php

namespace App\Http\Controllers\Operaciones;

use App\Http\Controllers\Controller;
use App\Models\Almacen;
use App\Models\CentroCosto;
use App\Models\Producto;
use App\Models\Trabajador;
use App\Models\ValeSalida;
use App\Services\MovimientoService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class ValeSalidaController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Operaciones/Vales/Index', [
            'vales' => ValeSalida::query()
                ->with(['almacen', 'centroCosto', 'solicitante', 'trabajador', 'movimiento', 'detalles.producto.unidad'])
                ->latest('id')
                ->paginate(25)
                ->withQueryString()
                ->through(fn (ValeSalida $vale) => $this->presentarVale($vale)),
            'productos' => Producto::query()
                ->with(['unidad', 'familia'])
                ->when(Schema::hasColumn('productos', 'estado'), fn ($query) => $query->where('estado', 'activo'))
                ->when(Schema::hasColumn('productos', 'activo'), fn ($query) => $query->where('activo', true))
                ->orderBy('nombre')
                ->get(),
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
            'trabajadores' => $this->trabajadoresParaSelector(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedVale($request);

        DB::transaction(function () use ($data, $request) {
            $trabajador = Schema::hasTable('trabajadores') && ! empty($data['trabajador_id'])
                ? Trabajador::query()->find($data['trabajador_id'])
                : null;
            $receptorNombre = $trabajador ? $this->nombreTrabajador($trabajador) : ($data['receptor_nombre'] ?? null);
            $receptorDni = $trabajador ? $this->dniTrabajador($trabajador) : ($data['receptor_dni'] ?? null);

            $vale = ValeSalida::query()->create([
                'numero' => $this->generarNumero(),
                'almacen_id' => $data['almacen_id'],
                'centro_costo_id' => $data['centro_costo_id'] ?? null,
                'solicitante_id' => $request->user()->id,
                ...(Schema::hasColumn('vales_salida', 'trabajador_id') && $trabajador ? ['trabajador_id' => $trabajador->id] : []),
                ...(Schema::hasColumn('vales_salida', 'receptor_nombre') && $receptorNombre ? ['receptor_nombre' => $receptorNombre] : []),
                ...(Schema::hasColumn('vales_salida', 'receptor_dni') && $receptorDni ? ['receptor_dni' => $receptorDni] : []),
                ...(Schema::hasColumn('vales_salida', 'destino') && $receptorNombre ? ['destino' => $receptorNombre] : []),
                ...(Schema::hasColumn('vales_salida', 'entregado_a') && $receptorNombre ? ['entregado_a' => $receptorNombre] : []),
                ...(Schema::hasColumn('vales_salida', 'dni_receptor') && $receptorDni ? ['dni_receptor' => $receptorDni] : []),
                'fecha' => $data['fecha'],
                'motivo' => $data['motivo'] ?? null,
                'estado' => 'pendiente',
            ]);

            foreach ($data['detalles'] as $detalle) {
                $vale->detalles()->create([
                    'producto_id' => $detalle['producto_id'],
                    'cantidad' => $detalle['cantidad'],
                ]);
            }
        });

        return back()->with('success', 'Vale de salida registrado.');
    }

    public function update(Request $request, ValeSalida $vale): RedirectResponse
    {
        if ($vale->estado !== 'pendiente') {
            return back()->with('error', 'Solo se pueden editar vales pendientes.');
        }

        $data = $this->validatedVale($request);

        DB::transaction(function () use ($data, $vale) {
            $trabajador = Schema::hasTable('trabajadores') && ! empty($data['trabajador_id'])
                ? Trabajador::query()->find($data['trabajador_id'])
                : null;
            $receptorNombre = $trabajador ? $this->nombreTrabajador($trabajador) : ($data['receptor_nombre'] ?? null);
            $receptorDni = $trabajador ? $this->dniTrabajador($trabajador) : ($data['receptor_dni'] ?? null);

            $vale->update([
                'almacen_id' => $data['almacen_id'],
                'centro_costo_id' => $data['centro_costo_id'] ?? null,
                ...(Schema::hasColumn('vales_salida', 'trabajador_id') && $trabajador ? ['trabajador_id' => $trabajador->id] : []),
                ...(Schema::hasColumn('vales_salida', 'receptor_nombre') && $receptorNombre ? ['receptor_nombre' => $receptorNombre] : []),
                ...(Schema::hasColumn('vales_salida', 'receptor_dni') && $receptorDni ? ['receptor_dni' => $receptorDni] : []),
                ...(Schema::hasColumn('vales_salida', 'destino') && $receptorNombre ? ['destino' => $receptorNombre] : []),
                ...(Schema::hasColumn('vales_salida', 'entregado_a') && $receptorNombre ? ['entregado_a' => $receptorNombre] : []),
                ...(Schema::hasColumn('vales_salida', 'dni_receptor') && $receptorDni ? ['dni_receptor' => $receptorDni] : []),
                'fecha' => $data['fecha'],
                'motivo' => $data['motivo'] ?? null,
            ]);

            $vale->detalles()->delete();

            foreach ($data['detalles'] as $detalle) {
                $vale->detalles()->create([
                    'producto_id' => $detalle['producto_id'],
                    'cantidad' => $detalle['cantidad'],
                ]);
            }
        });

        return back()->with('success', 'Vale de salida actualizado.');
    }

    public function deliver(Request $request, ValeSalida $vale, MovimientoService $service): RedirectResponse
    {
        if ($vale->estado !== 'pendiente') {
            return back()->with('error', 'Solo se pueden entregar vales pendientes.');
        }

        $vale->load('detalles');

        DB::transaction(function () use ($vale, $request, $service) {
            $movimientoId = $service->registrar([
                'tipo' => 'salida',
                'almacen_origen_id' => $vale->almacen_id,
                'centro_costo_id' => $vale->centro_costo_id,
                'fecha' => $vale->fecha?->toDateString() ?? now()->toDateString(),
                'documento' => $vale->numero,
                'observaciones' => $vale->motivo,
                'detalles' => $vale->detalles->map(fn ($detalle) => [
                    'producto_id' => $detalle->producto_id,
                    'cantidad' => $detalle->cantidad,
                ])->all(),
            ], $request->user()->id);

            $vale->update([
                'estado' => 'entregado',
                'movimiento_id' => $movimientoId,
            ]);
        });

        return back()->with('success', 'Vale entregado y stock actualizado.');
    }

    public function pdf(ValeSalida $vale)
    {
        $vale->load(['almacen', 'centroCosto', 'solicitante', 'trabajador', 'detalles.producto.unidad']);

        return Pdf::loadView('pdf.vale-salida', ['vale' => $vale])
            ->setPaper('a4', 'landscape')
            ->stream($vale->numero.'.pdf');
    }

    private function generarNumero(): string
    {
        $year = now()->year;
        $count = ValeSalida::query()
            ->where('numero', 'like', "VAL-{$year}-%")
            ->lockForUpdate()
            ->count() + 1;

        return sprintf('VAL-%s-%06d', $year, $count);
    }

    private function validatedVale(Request $request): array
    {
        return $request->validate([
            'almacen_id' => ['required', 'exists:almacenes,id'],
            'centro_costo_id' => ['nullable', 'exists:centros_costos,id'],
            'trabajador_id' => [Schema::hasTable('trabajadores') ? 'nullable' : 'nullable', Schema::hasTable('trabajadores') ? 'exists:trabajadores,id' : 'nullable'],
            'receptor_nombre' => ['nullable', 'string', 'max:255'],
            'receptor_dni' => ['nullable', 'string', 'max:20'],
            'fecha' => ['required', 'date'],
            'destino' => ['nullable', 'string', 'max:255'],
            'motivo' => ['nullable', 'string'],
            'observaciones' => ['nullable', 'string'],
            'detalles' => ['required', 'array', 'min:1'],
            'detalles.*.producto_id' => ['required', 'exists:productos,id'],
            'detalles.*.cantidad' => ['required', 'numeric', 'min:0.0001'],
            'detalles.*.observaciones' => ['nullable', 'string'],
        ]);
    }

    private function trabajadoresParaSelector()
    {
        if (! Schema::hasTable('trabajadores')) {
            return [];
        }

        $columns = Schema::getColumnListing('trabajadores');
        $query = Trabajador::query();

        if (in_array('estado', $columns, true)) {
            $query->where('estado', 'activo');
        }

        $orderColumn = collect(['nombres', 'nombre', 'name', 'apellido_paterno', 'id'])
            ->first(fn ($column) => in_array($column, $columns, true));

        if ($orderColumn) {
            $query->orderBy($orderColumn);
        }

        return $query->get()->map(fn ($trabajador) => [
            'id' => $trabajador->id,
            'dni' => $this->dniTrabajador($trabajador),
            'nombres' => $this->nombreTrabajador($trabajador),
            'apellidos' => '',
            'cargo' => $trabajador->cargo ?? $trabajador->puesto ?? null,
            'area' => $trabajador->area ?? null,
            'estado' => $trabajador->estado ?? 'activo',
        ])->values();
    }

    private function nombreTrabajador(Trabajador $trabajador): string
    {
        return trim(collect([
            $trabajador->nombres ?? null,
            $trabajador->apellidos ?? null,
        ])->filter()->join(' ')) ?: trim(collect([
            $trabajador->nombre ?? null,
            $trabajador->apellido_paterno ?? null,
            $trabajador->apellido_materno ?? null,
        ])->filter()->join(' ')) ?: ($trabajador->name ?? 'Trabajador');
    }

    private function dniTrabajador(Trabajador $trabajador): ?string
    {
        return $trabajador->dni
            ?? $trabajador->documento
            ?? $trabajador->numero_documento
            ?? $trabajador->nro_documento
            ?? null;
    }

    private function presentarVale(ValeSalida $vale): array
    {
        $trabajadorNombre = $vale->trabajador ? $this->nombreTrabajador($vale->trabajador) : null;
        $trabajadorDni = $vale->trabajador ? $this->dniTrabajador($vale->trabajador) : null;

        return [
            ...$vale->toArray(),
            'trabajador_id' => $vale->trabajador_id ?? $vale->trabajador?->id,
            'receptor_nombre' => $vale->receptor_nombre ?? $vale->entregado_a ?? $trabajadorNombre ?? $vale->destino ?? null,
            'receptor_dni' => $vale->receptor_dni ?? $vale->dni_receptor ?? $trabajadorDni ?? null,
            'trabajador' => $vale->trabajador ? [
                'id' => $vale->trabajador->id,
                'dni' => $trabajadorDni,
                'nombres' => $trabajadorNombre,
                'apellidos' => '',
                'nombre' => $trabajadorNombre,
            ] : null,
            'almacen' => $vale->almacen,
            'centro_costo' => $vale->centroCosto,
            'centroCosto' => $vale->centroCosto,
            'solicitante' => $vale->solicitante,
            'movimiento' => $vale->movimiento,
            'detalles' => $vale->detalles,
        ];
    }
}
