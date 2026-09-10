<?php

namespace App\Http\Controllers\Administracion;

use App\Http\Controllers\Controller;
use App\Models\CentroCosto;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CentroCostoController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Administracion/CentrosCostos/Index', [
            'centros' => CentroCosto::query()
                ->with('responsable:id,name')
                ->latest()
                ->get(),
            'usuarios' => User::query()
                ->where('activo', true)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        CentroCosto::query()->create($this->validateData($request));

        return back()->with('success', 'Centro de costo creado.');
    }

    public function update(Request $request, CentroCosto $centroCosto): RedirectResponse
    {
        $centroCosto->update($this->validateData($request, $centroCosto));

        return back()->with('success', 'Centro de costo actualizado.');
    }

    public function destroy(CentroCosto $centroCosto): RedirectResponse
    {
        $centroCosto->update(['activo' => false]);
        $centroCosto->delete();

        return back()->with('success', 'Centro de costo desactivado.');
    }

    private function validateData(Request $request, ?CentroCosto $centroCosto = null): array
    {
        return $request->validate([
            'codigo' => ['required', 'string', 'max:30', Rule::unique('centros_costos', 'codigo')->ignore($centroCosto)],
            'nombre' => ['required', 'string', 'max:255'],
            'tipo' => ['required', 'string', 'max:40'],
            'responsable_id' => ['nullable', 'exists:users,id'],
            'ubicacion' => ['nullable', 'string', 'max:255'],
            'activo' => ['required', 'boolean'],
        ]);
    }
}
