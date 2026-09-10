<?php

namespace App\Http\Controllers\Administracion;

use App\Http\Controllers\Controller;
use App\Models\EmpresaConfiguracion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmpresaConfiguracionController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('Administracion/Empresa/Edit', [
            'empresa' => EmpresaConfiguracion::query()->first(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'razon_social' => ['required', 'string', 'max:255'],
            'nombre_comercial' => ['nullable', 'string', 'max:255'],
            'ruc' => ['required', 'string', 'size:11'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'ciudad' => ['required', 'string', 'max:100'],
            'pais' => ['required', 'string', 'max:100'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'rubro' => ['required', 'string', 'max:255'],
            'moneda' => ['required', 'string', 'size:3'],
            'metodo_valorizacion' => ['required', 'in:promedio_ponderado'],
            'bloquear_stock_negativo' => ['required', 'boolean'],
        ]);

        $empresa = EmpresaConfiguracion::query()->firstOrNew();
        $empresa->fill($data)->save();

        return back()->with('success', 'Configuracion de empresa actualizada.');
    }
}
