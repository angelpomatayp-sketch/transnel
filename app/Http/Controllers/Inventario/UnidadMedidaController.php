<?php

namespace App\Http\Controllers\Inventario;

use App\Http\Controllers\Controller;
use App\Models\UnidadMedida;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UnidadMedidaController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Inventario/Unidades/Index', [
            'unidades' => UnidadMedida::query()->latest()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        UnidadMedida::query()->create($this->validateData($request));

        return back()->with('success', 'Unidad de medida creada.');
    }

    public function update(Request $request, UnidadMedida $unidad): RedirectResponse
    {
        $unidad->update($this->validateData($request, $unidad));

        return back()->with('success', 'Unidad de medida actualizada.');
    }

    public function destroy(UnidadMedida $unidad): RedirectResponse
    {
        if ($unidad->productos()->exists()) {
            return back()->withErrors(['unidad' => 'No se puede desactivar una unidad con productos asociados.']);
        }

        $unidad->update(['activo' => false]);
        $unidad->delete();

        return back()->with('success', 'Unidad de medida desactivada.');
    }

    private function validateData(Request $request, ?UnidadMedida $unidad = null): array
    {
        return $request->validate([
            'codigo' => ['required', 'string', 'max:30', Rule::unique('unidades_medida', 'codigo')->ignore($unidad)],
            'nombre' => ['required', 'string', 'max:255'],
            'abreviatura' => ['required', 'string', 'max:20'],
            'activo' => ['required', 'boolean'],
        ]);
    }
}
