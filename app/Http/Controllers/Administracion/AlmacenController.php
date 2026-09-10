<?php

namespace App\Http\Controllers\Administracion;

use App\Http\Controllers\Controller;
use App\Models\Almacen;
use App\Models\CentroCosto;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AlmacenController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Administracion/Almacenes/Index', [
            'almacenes' => Almacen::query()
                ->with(['centroCosto:id,codigo,nombre', 'responsable:id,name'])
                ->latest()
                ->get(),
            'centros' => CentroCosto::query()
                ->where('activo', true)
                ->orderBy('nombre')
                ->get(['id', 'codigo', 'nombre']),
            'usuarios' => User::query()
                ->where('activo', true)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Almacen::query()->create($this->validateData($request));

        return back()->with('success', 'Almacen creado.');
    }

    public function update(Request $request, Almacen $almacen): RedirectResponse
    {
        $almacen->update($this->validateData($request, $almacen));

        return back()->with('success', 'Almacen actualizado.');
    }

    public function destroy(Almacen $almacen): RedirectResponse
    {
        $almacen->update(['activo' => false]);
        $almacen->delete();

        return back()->with('success', 'Almacen desactivado.');
    }

    private function validateData(Request $request, ?Almacen $almacen = null): array
    {
        return $request->validate([
            'codigo' => ['required', 'string', 'max:30', Rule::unique('almacenes', 'codigo')->ignore($almacen)],
            'nombre' => ['required', 'string', 'max:255'],
            'tipo' => ['required', 'string', 'max:40'],
            'ubicacion' => ['nullable', 'string', 'max:255'],
            'centro_costo_id' => ['nullable', 'exists:centros_costos,id'],
            'responsable_id' => ['nullable', 'exists:users,id'],
            'activo' => ['required', 'boolean'],
        ]);
    }
}
