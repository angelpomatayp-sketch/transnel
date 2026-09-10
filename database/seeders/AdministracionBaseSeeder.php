<?php

namespace Database\Seeders;

use App\Models\Almacen;
use App\Models\CentroCosto;
use App\Models\Trabajador;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdministracionBaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('email', 'admin@logistica.test')->first();

        $centro = CentroCosto::query()->updateOrCreate(
            ['codigo' => 'OBRA-001'],
            [
                'nombre' => 'Operacion Minera Principal',
                'tipo' => 'obra',
                'responsable_id' => $admin?->id,
                'ubicacion' => 'Lima, Peru',
                'activo' => true,
            ],
        );

        $almacen = Almacen::query()->updateOrCreate(
            ['codigo' => 'ALM-001'],
            [
                'nombre' => 'Almacen Central',
                'tipo' => 'principal',
                'ubicacion' => 'Lima, Peru',
                'centro_costo_id' => $centro->id,
                'responsable_id' => $admin?->id,
                'activo' => true,
            ],
        );

        if ($admin) {
            $admin->update([
                'centro_costo_id' => $centro->id,
                'almacen_id' => $almacen->id,
            ]);
        }

        Trabajador::query()->updateOrCreate(
            ['dni' => '12345678'],
            [
                'centro_costo_id' => $centro->id,
                'nombre' => 'Trabajador Demo',
                'cargo' => 'Operario de almacen',
                'telefono' => '+51 999 111 222',
                'fecha_ingreso' => now()->toDateString(),
                'activo' => true,
            ],
        );
    }
}
