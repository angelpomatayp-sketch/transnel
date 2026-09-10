<?php

namespace App\Http\Controllers\Administracion;

use App\Http\Controllers\Controller;
use App\Models\Almacen;
use App\Models\CentroCosto;
use App\Models\EppAsignacion;
use App\Models\Producto;
use App\Models\Trabajador;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class TrabajadorController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Administracion/Trabajadores/Index', [
            'trabajadores' => Trabajador::query()
                ->latest('id')
                ->get()
                ->map(fn (Trabajador $trabajador) => $this->presentarTrabajador($trabajador))
                ->values(),
            'eppAsignaciones' => Schema::hasTable('epp_asignaciones')
                ? EppAsignacion::query()
                    ->with(['trabajador', 'producto.unidad', 'producto.familia', 'almacen', 'centroCosto'])
                    ->latest('id')
                    ->get()
                    ->map(fn (EppAsignacion $asignacion) => [
                        'id' => $asignacion->id,
                        'codigo' => $asignacion->codigo,
                        'trabajador_id' => $asignacion->trabajador_id,
                        'trabajador' => $asignacion->trabajador,
                        'producto_id' => $asignacion->producto_id,
                        'producto' => $asignacion->producto,
                        'almacen' => $asignacion->almacen,
                        'centro_costo' => $asignacion->centroCosto,
                        'cantidad' => $asignacion->cantidad,
                        'talla' => $asignacion->talla,
                        'fecha_entrega' => $asignacion->fecha_entrega,
                        'fecha_vencimiento' => $asignacion->fecha_vencimiento,
                        'estado' => $asignacion->estado,
                        'observaciones' => $asignacion->observaciones,
                    ])
                    ->values()
                : [],
            'productosEpp' => $this->productosEpp(),
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

    public function store(Request $request): RedirectResponse
    {
        Trabajador::query()->create($this->payload($request));

        return back()->with('success', 'Trabajador registrado.');
    }

    public function update(Request $request, Trabajador $trabajador): RedirectResponse
    {
        $trabajador->update($this->payload($request, $trabajador));

        return back()->with('success', 'Trabajador actualizado.');
    }

    public function destroy(Trabajador $trabajador): RedirectResponse
    {
        if (Schema::hasTable('epp_asignaciones') && EppAsignacion::query()->where('trabajador_id', $trabajador->id)->exists()) {
            return back()->with('error', 'No se puede eliminar un trabajador con EPPs asociados.');
        }

        if (Schema::hasTable('vales_salida') && Schema::hasColumn('vales_salida', 'trabajador_id') && $trabajador->valesSalida()->exists()) {
            return back()->with('error', 'No se puede eliminar un trabajador con vales asociados.');
        }

        $trabajador->delete();

        return back()->with('success', 'Trabajador eliminado.');
    }

    public function eppKardexPdf(Trabajador $trabajador): HttpResponse
    {
        $asignaciones = Schema::hasTable('epp_asignaciones')
            ? EppAsignacion::query()
                ->with(['producto.unidad', 'producto.familia', 'almacen', 'centroCosto'])
                ->where('trabajador_id', $trabajador->id)
                ->orderBy('fecha_entrega')
                ->orderBy('id')
                ->get()
            : collect();

        $eventos = $asignaciones
            ->flatMap(function (EppAsignacion $asignacion) {
                $rows = [[
                    'fecha' => $asignacion->fecha_entrega,
                    'tipo' => 'Entrega',
                    'asignacion' => $asignacion,
                    'observaciones' => $asignacion->observaciones,
                ]];

                if ($asignacion->fecha_devolucion) {
                    $rows[] = [
                        'fecha' => $asignacion->fecha_devolucion,
                        'tipo' => match ($asignacion->estado) {
                            'renovado' => 'Renovacion',
                            'baja' => 'Baja',
                            default => 'Devolucion',
                        },
                        'asignacion' => $asignacion,
                        'observaciones' => $asignacion->motivo_devolucion,
                    ];
                }

                return $rows;
            })
            ->sortBy('fecha')
            ->values();

        $filename = 'kardex-epp-'.$trabajador->dni.'.pdf';

        return Pdf::loadView('pdf.trabajador-epp-kardex', [
            'trabajador' => $trabajador,
            'asignaciones' => $asignaciones,
            'eventos' => $eventos,
        ])->setPaper('a4', 'portrait')->stream($filename);
    }

    private function payload(Request $request, ?Trabajador $trabajador = null): array
    {
        $data = $request->validate([
            'dni' => ['required', 'string', 'max:20', Rule::unique('trabajadores', 'dni')->ignore($trabajador?->id)],
            'nombres' => ['nullable', 'string', 'max:120'],
            'apellidos' => ['nullable', 'string', 'max:120'],
            'nombre' => ['nullable', 'string', 'max:180'],
            'cargo' => ['nullable', 'string', 'max:120'],
            'area' => ['nullable', 'string', 'max:120'],
            'telefono' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:160'],
            'estado' => ['nullable', 'string', 'max:30'],
            'activo' => ['nullable'],
        ]);

        $nombre = trim($data['nombre'] ?? trim(($data['nombres'] ?? '').' '.($data['apellidos'] ?? '')));
        $estado = $data['estado'] ?? (((bool) ($data['activo'] ?? true)) ? 'activo' : 'inactivo');

        $primerNombre = strtok($nombre, ' ') ?: $nombre;

        $payload = [
            'dni' => $data['dni'],
            'nombre' => $nombre,
            'nombres' => $data['nombres'] ?? $primerNombre,
            'apellidos' => $data['apellidos'] ?? trim(str_replace($primerNombre, '', $nombre)),
            'cargo' => $data['cargo'] ?? null,
            'area' => $data['area'] ?? null,
            'telefono' => $data['telefono'] ?? null,
            'email' => $data['email'] ?? null,
            'estado' => $estado,
            'activo' => $estado === 'activo',
        ];

        return collect($payload)
            ->filter(fn ($value, $key) => Schema::hasColumn('trabajadores', $key))
            ->all();
    }

    private function presentarTrabajador(Trabajador $trabajador): array
    {
        $nombre = $trabajador->nombre ?: trim(($trabajador->nombres ?? '').' '.($trabajador->apellidos ?? ''));

        $primerNombre = strtok($nombre, ' ') ?: $nombre;

        return [
            'id' => $trabajador->id,
            'dni' => $trabajador->dni,
            'nombre' => $nombre,
            'nombres' => $trabajador->nombres ?? $primerNombre,
            'apellidos' => $trabajador->apellidos ?? trim(str_replace($primerNombre, '', $nombre)),
            'cargo' => $trabajador->cargo,
            'area' => $trabajador->area ?? null,
            'telefono' => $trabajador->telefono,
            'email' => $trabajador->email ?? null,
            'estado' => $trabajador->estado ?? ((bool) ($trabajador->activo ?? true) ? 'activo' : 'inactivo'),
            'activo' => (bool) ($trabajador->activo ?? true),
        ];
    }

    private function productosEpp()
    {
        return Producto::query()
            ->with(['unidad', 'familia'])
            ->whereHas('familia', function ($query) {
                $query->where(function ($familia) {
                    if (Schema::hasColumn('familias', 'es_epp')) {
                        $familia->where('es_epp', true);
                    }

                    $familia->orWhere('nombre', 'like', '%EPP%')
                        ->orWhere('nombre', 'like', '%proteccion%')
                        ->orWhere('codigo', 'like', '%EPP%');
                });
            })
            ->when(Schema::hasColumn('productos', 'activo'), fn ($query) => $query->where('activo', true))
            ->orderBy('nombre')
            ->get();
    }
}
