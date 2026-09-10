<?php

namespace App\Http\Controllers\Operaciones;

use App\Http\Controllers\Controller;
use App\Models\Almacen;
use App\Models\CentroCosto;
use App\Models\OrdenCompra;
use App\Models\OrdenCompraDetalle;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Requerimiento;
use App\Services\MovimientoService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CompraController extends Controller
{
    public function index(): Response
    {
        $this->ensureTables();

        return Inertia::render('Operaciones/Compras/Index', [
            'compras' => OrdenCompra::query()
                ->with(['proveedor', 'almacen', 'centroCosto', 'detalles.producto.unidad'])
                ->latest('id')
                ->paginate(25)
                ->withQueryString(),
            'proveedores' => Proveedor::query()
                ->where('activo', true)
                ->orderBy('razon_social')
                ->get(),
            'productos' => Producto::query()
                ->with(['unidad', 'familia'])
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
            'requerimientos' => Requerimiento::query()
                ->with(['detalles.producto.unidad', 'centroCosto', 'almacen'])
                ->where('estado', 'aprobado')
                ->latest('id')
                ->get()
                ->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureTables();

        $data = $request->validate([
            'proveedor_id' => ['required', 'exists:proveedores,id'],
            'requerimiento_id' => ['nullable', 'exists:requerimientos,id'],
            'almacen_id' => ['required', 'exists:almacenes,id'],
            'centro_costo_id' => ['nullable', 'exists:centros_costos,id'],
            'fecha' => ['required', 'date'],
            'fecha_entrega' => ['nullable', 'date'],
            'documento_referencia' => ['nullable', 'string', 'max:80'],
            'observaciones' => ['nullable', 'string'],
            'detalles' => ['required', 'array', 'min:1'],
            'detalles.*.producto_id' => ['required', 'exists:productos,id'],
            'detalles.*.cantidad' => ['required', 'numeric', 'min:0.0001'],
            'detalles.*.precio_unitario' => ['required', 'numeric', 'min:0'],
            'detalles.*.observaciones' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($data, $request) {
            $numero = $this->generarNumero();
            $subtotal = collect($data['detalles'])->sum(fn ($detalle) => round((float) $detalle['cantidad'] * (float) $detalle['precio_unitario'], 2));
            $igv = round($subtotal * 0.18, 2);

            $orden = OrdenCompra::query()->create([
                'numero' => $numero,
                'requerimiento_id' => $data['requerimiento_id'] ?? null,
                'proveedor_id' => $data['proveedor_id'],
                'almacen_id' => $data['almacen_id'],
                'centro_costo_id' => $data['centro_costo_id'] ?? null,
                'usuario_id' => $request->user()->id,
                'fecha' => $data['fecha'],
                'fecha_entrega' => $data['fecha_entrega'] ?? null,
                'estado' => 'pendiente',
                'documento_referencia' => $data['documento_referencia'] ?? null,
                'observaciones' => $data['observaciones'] ?? null,
                'subtotal' => $subtotal,
                'igv' => $igv,
                'total' => $subtotal + $igv,
            ]);

            foreach ($data['detalles'] as $detalle) {
                $orden->detalles()->create([
                    'producto_id' => $detalle['producto_id'],
                    'cantidad' => $detalle['cantidad'],
                    'cantidad_recibida' => 0,
                    'precio_unitario' => $detalle['precio_unitario'],
                    'subtotal' => round((float) $detalle['cantidad'] * (float) $detalle['precio_unitario'], 2),
                    'observaciones' => $detalle['observaciones'] ?? null,
                ]);
            }

            if (! empty($data['requerimiento_id'])) {
                Requerimiento::query()
                    ->where('id', $data['requerimiento_id'])
                    ->where('estado', 'aprobado')
                    ->update(['estado' => 'en_compra']);
            }
        });

        return back()->with('success', 'Orden de compra registrada.');
    }

    public function recibir(Request $request, OrdenCompra $compra, MovimientoService $movimientoService): RedirectResponse
    {
        $this->ensureTables();

        if (in_array($compra->estado, ['recibido', 'anulado'], true)) {
            return back()->with('error', 'La orden ya no permite recepciones.');
        }

        $data = $request->validate([
            'documento_recepcion' => ['nullable', 'string', 'max:80'],
            'detalles' => ['required', 'array', 'min:1'],
            'detalles.*.id' => ['required', 'integer'],
            'detalles.*.cantidad_recibida' => ['nullable', 'numeric', 'min:0'],
        ]);

        $compra->load('detalles.producto');
        $detalles = $compra->detalles->keyBy('id');
        $recibidos = [];

        foreach ($data['detalles'] as $detalleData) {
            $detalle = $detalles->get((int) $detalleData['id']);
            if (! $detalle) {
                continue;
            }

            $cantidad = (float) ($detalleData['cantidad_recibida'] ?? 0);
            $pendiente = max(0, (float) $detalle->cantidad - (float) $detalle->cantidad_recibida);

            if ($cantidad > $pendiente) {
                throw ValidationException::withMessages([
                    'detalles' => "La cantidad recibida de {$detalle->producto?->nombre} supera lo pendiente.",
                ]);
            }

            if ($cantidad > 0) {
                $recibidos[] = [
                    'detalle' => $detalle,
                    'cantidad' => $cantidad,
                ];
            }
        }

        if ($recibidos === []) {
            return back()->with('error', 'Ingrese al menos una cantidad recibida.');
        }

        DB::transaction(function () use ($compra, $data, $request, $movimientoService, $recibidos) {
            $movimientoService->registrar([
                'tipo' => 'entrada',
                'almacen_destino_id' => $compra->almacen_id,
                'centro_costo_id' => $compra->centro_costo_id,
                'fecha' => now()->toDateString(),
                'documento' => $data['documento_recepcion'] ?? $compra->numero,
                'observaciones' => 'Recepcion de orden de compra '.$compra->numero,
                'detalles' => collect($recibidos)->map(fn (array $item) => [
                    'producto_id' => $item['detalle']->producto_id,
                    'cantidad' => $item['cantidad'],
                    'costo_unitario' => $item['detalle']->precio_unitario,
                ])->all(),
            ], $request->user()->id);

            foreach ($recibidos as $item) {
                $item['detalle']->update([
                    'cantidad_recibida' => (float) $item['detalle']->cantidad_recibida + $item['cantidad'],
                ]);
            }

            $compra->refresh()->load('detalles');
            $todoRecibido = $compra->detalles->every(fn (OrdenCompraDetalle $detalle) => (float) $detalle->cantidad_recibida >= (float) $detalle->cantidad);

            $compra->update([
                'estado' => $todoRecibido ? 'recibido' : 'recibido_parcial',
            ]);
        });

        return back()->with('success', 'Recepcion registrada.');
    }

    public function anular(Request $request, OrdenCompra $compra): RedirectResponse
    {
        if ($compra->estado === 'recibido') {
            return back()->with('error', 'No se puede anular una compra ya recibida totalmente.');
        }

        $request->validate([
            'motivo' => ['required', 'string', 'min:3'],
        ]);

        $compra->update([
            'estado' => 'anulado',
            'observaciones' => trim(($compra->observaciones ? $compra->observaciones."\n" : '').'Anulado: '.$request->string('motivo')),
        ]);

        return back()->with('success', 'Orden de compra anulada.');
    }

    public function pdf(OrdenCompra $compra): HttpResponse
    {
        $compra->load(['proveedor', 'almacen', 'centroCosto', 'requerimiento', 'detalles.producto.unidad', 'usuario']);

        return Pdf::loadView('pdf.orden-compra', [
            'compra' => $compra,
        ])->setPaper('a4', 'portrait')->stream($compra->numero.'.pdf');
    }

    private function generarNumero(): string
    {
        $year = now()->format('Y');
        $count = OrdenCompra::query()
            ->where('numero', 'like', "OC-{$year}-%")
            ->lockForUpdate()
            ->count() + 1;

        return sprintf('OC-%s-%06d', $year, $count);
    }

    private function ensureTables(): void
    {
        if (! Schema::hasTable('proveedores')) {
            Schema::create('proveedores', function ($table) {
                $table->id();
                $table->string('ruc', 20)->nullable()->unique();
                $table->string('razon_social');
                $table->string('nombre_comercial')->nullable();
                $table->string('contacto')->nullable();
                $table->string('telefono', 40)->nullable();
                $table->string('email')->nullable();
                $table->string('direccion')->nullable();
                $table->boolean('activo')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('ordenes_compra')) {
            Schema::create('ordenes_compra', function ($table) {
                $table->id();
                $table->string('numero')->unique();
                $table->foreignId('requerimiento_id')->nullable()->constrained('requerimientos')->nullOnDelete();
                $table->foreignId('proveedor_id')->nullable()->constrained('proveedores')->nullOnDelete();
                $table->foreignId('almacen_id')->nullable()->constrained('almacenes')->nullOnDelete();
                $table->foreignId('centro_costo_id')->nullable()->constrained('centros_costos')->nullOnDelete();
                $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
                $table->date('fecha');
                $table->date('fecha_entrega')->nullable();
                $table->string('estado')->default('pendiente');
                $table->string('documento_referencia')->nullable();
                $table->text('observaciones')->nullable();
                $table->decimal('subtotal', 14, 2)->default(0);
                $table->decimal('igv', 14, 2)->default(0);
                $table->decimal('total', 14, 2)->default(0);
                $table->timestamps();
                $table->softDeletes();
            });
        } elseif (! Schema::hasColumn('ordenes_compra', 'requerimiento_id')) {
            Schema::table('ordenes_compra', function ($table) {
                $table->foreignId('requerimiento_id')->nullable()->after('numero')->constrained('requerimientos')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('orden_compra_detalles')) {
            Schema::create('orden_compra_detalles', function ($table) {
                $table->id();
                $table->foreignId('orden_compra_id')->constrained('ordenes_compra')->cascadeOnDelete();
                $table->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
                $table->decimal('cantidad', 12, 4);
                $table->decimal('cantidad_recibida', 12, 4)->default(0);
                $table->decimal('precio_unitario', 14, 4)->default(0);
                $table->decimal('subtotal', 14, 2)->default(0);
                $table->text('observaciones')->nullable();
                $table->timestamps();
            });
        }
    }
}
