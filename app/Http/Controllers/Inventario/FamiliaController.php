<?php

namespace App\Http\Controllers\Inventario;

use App\Http\Controllers\Controller;
use App\Models\Familia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class FamiliaController extends Controller
{
    private const CATEGORIAS_EPP = [
        'Protección de Cabeza',
        'Protección Ocular',
        'Protección Auditiva',
        'Protección Respiratoria',
        'Protección de Manos',
        'Protección de Pies',
        'Protección Corporal',
        'Trabajo en Altura',
        'Otros',
    ];

    public function index(): Response
    {
        return Inertia::render('Inventario/Familias/Index', [
            'familias' => Familia::query()
                ->withCount('productos')
                ->orderBy('nombre')
                ->get(),
            'categoriasEpp' => self::CATEGORIAS_EPP,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Familia::query()->create($this->validatedData($request));

        return back()->with('success', 'Familia registrada.');
    }

    public function update(Request $request, Familia $familia): RedirectResponse
    {
        $familia->update($this->validatedData($request, $familia));

        return back()->with('success', 'Familia actualizada.');
    }

    public function destroy(Familia $familia): RedirectResponse
    {
        if ($familia->productos()->exists()) {
            throw ValidationException::withMessages([
                'familia' => 'No se puede eliminar una familia con productos asociados.',
            ]);
        }

        $familia->delete();

        return back()->with('success', 'Familia eliminada.');
    }

    private function validatedData(Request $request, ?Familia $familia = null): array
    {
        $data = $request->validate([
            'codigo' => [
                'required',
                'string',
                'max:20',
                Rule::unique('familias', 'codigo')->ignore($familia?->id),
            ],
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'activo' => ['required', 'boolean'],
            'es_epp' => ['required', 'boolean'],
            'categoria_epp' => [
                'nullable',
                'string',
                Rule::in(self::CATEGORIAS_EPP),
                Rule::requiredIf((bool) $request->boolean('es_epp')),
            ],
        ]);

        $data['codigo'] = mb_strtoupper(trim($data['codigo']));
        $data['categoria_epp'] = $data['es_epp'] ? ($data['categoria_epp'] ?? null) : null;

        return $data;
    }
}
