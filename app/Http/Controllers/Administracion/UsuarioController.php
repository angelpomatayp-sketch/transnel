<?php

namespace App\Http\Controllers\Administracion;

use App\Http\Controllers\Controller;
use App\Models\Almacen;
use App\Models\CentroCosto;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UsuarioController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Administracion/Usuarios/Index', [
            'usuarios' => User::query()
                ->with(['roles:id,name', 'centroCosto:id,codigo,nombre', 'almacen:id,codigo,nombre'])
                ->latest()
                ->get(),
            'roles' => Role::query()->orderBy('name')->get(['id', 'name']),
            'centros' => CentroCosto::query()->where('activo', true)->orderBy('nombre')->get(['id', 'codigo', 'nombre']),
            'almacenes' => Almacen::query()->where('activo', true)->orderBy('nombre')->get(['id', 'codigo', 'nombre']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $role = $data['role'];
        unset($data['role']);

        $data['password'] = Hash::make($data['password']);

        $user = User::query()->create($data);
        $user->syncRoles([$role]);

        return back()->with('success', 'Usuario creado.');
    }

    public function update(Request $request, User $usuario): RedirectResponse
    {
        $data = $this->validateData($request, $usuario);
        $role = $data['role'];
        unset($data['role']);

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $usuario->update($data);
        $usuario->syncRoles([$role]);

        return back()->with('success', 'Usuario actualizado.');
    }

    public function destroy(Request $request, User $usuario): RedirectResponse
    {
        if ($request->user()->is($usuario)) {
            return back()->withErrors(['usuario' => 'No puedes desactivar tu propio usuario.']);
        }

        $usuario->update(['activo' => false]);
        $usuario->delete();

        return back()->with('success', 'Usuario desactivado.');
    }

    private function validateData(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8'],
            'dni' => ['nullable', 'string', 'max:20'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'centro_costo_id' => ['nullable', 'exists:centros_costos,id'],
            'almacen_id' => ['nullable', 'exists:almacenes,id'],
            'activo' => ['required', 'boolean'],
            'role' => ['required', 'exists:roles,name'],
        ]);
    }
}
