<?php

namespace App\Http\Controllers\Administracion;

use App\Http\Controllers\Controller;
use App\Models\Proveedor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProveedorController extends Controller
{
    public function index(): Response
    {
        $this->ensureTable();

        return Inertia::render('Administracion/Proveedores/Index', [
            'proveedores' => Proveedor::query()
                ->latest('id')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureTable();

        Proveedor::query()->create($this->validated($request));

        return back()->with('success', 'Proveedor registrado.');
    }

    public function update(Request $request, Proveedor $proveedor): RedirectResponse
    {
        $this->ensureTable();

        $proveedor->update($this->validated($request, $proveedor));

        return back()->with('success', 'Proveedor actualizado.');
    }

    public function destroy(Proveedor $proveedor): RedirectResponse
    {
        $this->ensureTable();

        if ($proveedor->ordenesCompra()->exists()) {
            return back()->with('error', 'No se puede eliminar un proveedor con compras asociadas.');
        }

        $proveedor->delete();

        return back()->with('success', 'Proveedor eliminado.');
    }

    private function validated(Request $request, ?Proveedor $proveedor = null): array
    {
        return $request->validate([
            'ruc' => ['nullable', 'string', 'max:20', Rule::unique('proveedores', 'ruc')->ignore($proveedor?->id)],
            'razon_social' => ['required', 'string', 'max:180'],
            'nombre_comercial' => ['nullable', 'string', 'max:180'],
            'contacto' => ['nullable', 'string', 'max:160'],
            'telefono' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:160'],
            'direccion' => ['nullable', 'string', 'max:220'],
            'activo' => ['boolean'],
        ]);
    }

    private function ensureTable(): void
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
    }
}
