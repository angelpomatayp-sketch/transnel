<?php

namespace Database\Seeders;

use App\Models\EmpresaConfiguracion;
use Illuminate\Database\Seeder;

class EmpresaConfiguracionSeeder extends Seeder
{
    public function run(): void
    {
        EmpresaConfiguracion::query()->updateOrCreate(
            ['ruc' => '20601234567'],
            [
                'razon_social' => 'Contratistas Asociados Pacifico S.R.L.',
                'nombre_comercial' => 'Logistica Pacifico',
                'direccion' => 'Av. Javier Prado Este 1234',
                'ciudad' => 'Lima',
                'pais' => 'Peru',
                'telefono' => '+51 1 555 0100',
                'email' => 'logistica@pacifico.test',
                'rubro' => 'Servicios logisticos y contratistas para mineria',
                'moneda' => 'PEN',
                'metodo_valorizacion' => 'promedio_ponderado',
                'bloquear_stock_negativo' => true,
            ],
        );
    }
}
