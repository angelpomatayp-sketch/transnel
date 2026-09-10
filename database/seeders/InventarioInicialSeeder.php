<?php

namespace Database\Seeders;

use App\Models\Almacen;
use App\Models\Producto;
use App\Models\User;
use App\Services\InventarioService;
use Illuminate\Database\Seeder;

class InventarioInicialSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('email', 'admin@logistica.test')->first();
        $almacen = Almacen::query()->where('codigo', 'ALM-001')->first();

        if (! $admin || ! $almacen) {
            return;
        }

        $detalles = Producto::query()
            ->orderBy('codigo')
            ->get()
            ->map(fn (Producto $producto) => [
                'producto_id' => $producto->id,
                'cantidad' => $producto->es_epp ? 25 : 50,
                'costo_unitario' => $producto->costo_referencial ?: 1,
            ])
            ->all();

        if ($detalles === []) {
            return;
        }

        app(InventarioService::class)->registrarMovimiento([
            'tipo' => 'ENTRADA',
            'almacen_destino_id' => $almacen->id,
            'usuario_id' => $admin->id,
            'fecha' => now()->toDateString(),
            'documento' => 'INI-001',
            'observaciones' => 'Carga inicial de inventario demo.',
            'detalles' => $detalles,
        ]);
    }
}
