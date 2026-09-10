<?php

namespace App\Http\Controllers\Operaciones;

use App\Http\Controllers\Controller;
use App\Models\Almacen;
use App\Models\CentroCosto;
use App\Models\Producto;
use App\Models\Requerimiento;
use App\Models\RequerimientoHistorial;
use App\Services\RequerimientoService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RequerimientoController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Operaciones/Requerimientos/Index', [
            'requerimientos' => Requerimiento::query()
                ->with(['solicitante:id,name', 'almacen:id,codigo,nombre', 'centroCosto:id,codigo,nombre', 'detalles.producto:id,codigo,nombre'])
                ->latest('id')
                ->get(),
            'productos' => Producto::query()
                ->where('activo', true)
                ->orderBy('nombre')
                ->get(['id', 'codigo', 'nombre']),
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

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'estado' => $request->input('estado', 'enviado'),
            'fecha_requerida' => $request->input('fecha_requerida', $request->input('fecha')),
        ]);

        $isDraft = $request->input('estado', 'enviado') === 'borrador';
        $data = $this->validateRequestData($request, $isDraft);

        DB::transaction(function () use ($data, $request, $isDraft) {
            $requerimiento = Requerimiento::query()->create([
                'numero' => $this->generarNumero(),
                'solicitante_id' => $request->user()->id,
                'almacen_id' => $data['almacen_id'] ?? null,
                'centro_costo_id' => $data['centro_costo_id'] ?? null,
                'fecha' => $data['fecha_requerida'] ?? now()->toDateString(),
                'prioridad' => $data['prioridad'],
                'motivo' => $data['motivo'] ?? null,
                'observaciones' => $data['observaciones'] ?? null,
                'estado' => $isDraft ? 'borrador' : 'pendiente',
            ]);

            foreach ($data['detalles'] ?? [] as $detalle) {
                $requerimiento->detalles()->create([
                    'producto_id' => $detalle['producto_id'],
                    'cantidad_solicitada' => $detalle['cantidad_solicitada'],
                    'especificaciones' => $detalle['especificaciones'] ?? null,
                ]);
            }
        });

        return back()->with('success', $isDraft ? 'Borrador guardado.' : 'Requerimiento enviado.');
    }

    public function update(Request $request, Requerimiento $requerimiento): RedirectResponse
    {
        if ($requerimiento->estado !== 'borrador') {
            throw ValidationException::withMessages([
                'requerimiento' => 'Solo se pueden editar requerimientos en borrador.',
            ]);
        }

        $request->merge([
            'estado' => $request->input('estado', 'borrador'),
            'fecha_requerida' => $request->input('fecha_requerida', $request->input('fecha')),
        ]);

        $isDraft = $request->input('estado', 'borrador') === 'borrador';
        $data = $this->validateRequestData($request, $isDraft);

        DB::transaction(function () use ($data, $requerimiento, $isDraft) {
            $requerimiento->update([
                'almacen_id' => $data['almacen_id'] ?? null,
                'centro_costo_id' => $data['centro_costo_id'] ?? null,
                'fecha' => $data['fecha_requerida'] ?? now()->toDateString(),
                'prioridad' => $data['prioridad'],
                'motivo' => $data['motivo'] ?? null,
                'observaciones' => $data['observaciones'] ?? null,
                'estado' => $isDraft ? 'borrador' : 'pendiente',
            ]);

            $requerimiento->detalles()->delete();

            foreach ($data['detalles'] ?? [] as $detalle) {
                $requerimiento->detalles()->create([
                    'producto_id' => $detalle['producto_id'],
                    'cantidad_solicitada' => $detalle['cantidad_solicitada'],
                    'especificaciones' => $detalle['especificaciones'] ?? null,
                ]);
            }
        });

        return back()->with('success', $isDraft ? 'Borrador actualizado.' : 'Requerimiento enviado.');
    }

    public function send(Request $request, Requerimiento $requerimiento): RedirectResponse
    {
        if ($requerimiento->estado !== 'borrador') {
            throw ValidationException::withMessages([
                'requerimiento' => 'Solo se pueden enviar requerimientos en borrador.',
            ]);
        }

        if ($requerimiento->detalles()->count() === 0 || blank($requerimiento->centro_costo_id)) {
            throw ValidationException::withMessages([
                'requerimiento' => 'Complete obra/unidad y al menos un producto antes de enviar.',
            ]);
        }

        $requerimiento->update(['estado' => 'pendiente']);

        return back()->with('success', 'Requerimiento enviado.');
    }

    private function generarNumero(): string
    {
        $year = now()->format('Y');
        $count = Requerimiento::query()
            ->where('numero', 'like', "REQ-{$year}-%")
            ->lockForUpdate()
            ->count() + 1;

        return sprintf('REQ-%s-%06d', $year, $count);
    }

    private function validateRequestData(Request $request, bool $isDraft): array
    {
        return $request->validate([
            'almacen_id' => ['required', 'exists:almacenes,id'],
            'centro_costo_id' => ['nullable', 'exists:centros_costos,id'],
            'fecha_requerida' => [$isDraft ? 'nullable' : 'required', 'date'],
            'prioridad' => ['required', Rule::in(['baja', 'normal', 'alta', 'urgente'])],
            'motivo' => ['nullable', 'string'],
            'observaciones' => ['nullable', 'string'],
            'estado' => ['required', Rule::in(['borrador', 'enviado'])],
            'detalles' => $isDraft ? ['nullable', 'array'] : ['required', 'array', 'min:1'],
            'detalles.*.producto_id' => ['required_with:detalles', 'exists:productos,id'],
            'detalles.*.cantidad_solicitada' => ['required_with:detalles', 'numeric', 'min:0.0001'],
            'detalles.*.especificaciones' => ['nullable', 'string'],
        ]);
    }

    public function approve(Request $request, Requerimiento $requerimiento, RequerimientoService $service): RedirectResponse
    {
        $estadoAnterior = $requerimiento->estado;
        $service->aprobar($requerimiento, $request->user()->id, $request->string('comentario')->toString());
        $this->registrarHistorial($requerimiento->refresh(), 'aprobado', $estadoAnterior, 'aprobado', $request->user()->id, $request->string('comentario')->toString());

        return back()->with('success', 'Requerimiento aprobado.');
    }

    public function generateVale(Requerimiento $requerimiento, RequerimientoService $service): RedirectResponse
    {
        $service->generarVale($requerimiento);

        return back()->with('success', 'Vale de salida generado.');
    }

    public function observe(Request $request, Requerimiento $requerimiento): RedirectResponse
    {
        $data = $request->validate([
            'comentario' => ['required', 'string', 'min:3'],
        ]);

        $this->cambiarEstado($requerimiento, 'observado', 'observado', $request->user()->id, $data['comentario']);

        return back()->with('success', 'Requerimiento observado.');
    }

    public function rejectWithHistory(Request $request, Requerimiento $requerimiento): RedirectResponse
    {
        $data = $request->validate([
            'comentario' => ['required', 'string', 'min:3'],
        ]);

        $this->cambiarEstado($requerimiento, 'rechazado', 'rechazado', $request->user()->id, $data['comentario']);

        return back()->with('success', 'Requerimiento rechazado.');
    }

    public function cancel(Request $request, Requerimiento $requerimiento): RedirectResponse
    {
        $data = $request->validate([
            'comentario' => ['required', 'string', 'min:3'],
        ]);

        $this->cambiarEstado($requerimiento, 'anulado', 'anulado', $request->user()->id, $data['comentario']);

        return back()->with('success', 'Requerimiento anulado.');
    }

    private function cambiarEstado(Requerimiento $requerimiento, string $estado, string $accion, int $userId, ?string $comentario = null): void
    {
        DB::transaction(function () use ($requerimiento, $estado, $accion, $userId, $comentario) {
            $anterior = $requerimiento->estado;

            $requerimiento->update(['estado' => $estado]);

            $this->registrarHistorial($requerimiento, $estado, $anterior, $accion, $userId, $comentario);
        });
    }

    private function registrarHistorial(Requerimiento $requerimiento, string $estado, string $anterior, string $accion, int $userId, ?string $comentario = null): void
    {
        if (! Schema::hasTable('requerimiento_historial')) {
            return;
        }

        RequerimientoHistorial::query()->create([
            'requerimiento_id' => $requerimiento->id,
            'usuario_id' => $userId,
            'accion' => $accion,
            'estado_anterior' => $anterior,
            'estado_nuevo' => $estado,
            'comentario' => $comentario,
        ]);
    }

    public function reject(Request $request, Requerimiento $requerimiento, RequerimientoService $service): RedirectResponse
    {
        $request->validate([
            'comentario' => ['required', 'string'],
        ]);

        $service->rechazar($requerimiento, $request->user()->id, $request->string('comentario')->toString());

        return back()->with('success', 'Requerimiento rechazado.');
    }

    public function pdf(Requerimiento $requerimiento)
    {
        $requerimiento->load(['solicitante', 'almacen', 'centroCosto', 'detalles.producto.unidad']);

        return Pdf::loadView('pdf.requerimiento', compact('requerimiento'))->stream("requerimiento-{$requerimiento->numero}.pdf");
    }
}
