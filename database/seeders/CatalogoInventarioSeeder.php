<?php

namespace Database\Seeders;

use App\Models\Familia;
use App\Models\Producto;
use App\Models\UnidadMedida;
use Illuminate\Database\Seeder;

class CatalogoInventarioSeeder extends Seeder
{
    public function run(): void
    {
        $familiasEpp = [
            'EPP-CAB' => ['nombre' => 'Protección de Cabeza', 'categoria_epp' => 'Protección de Cabeza'],
            'EPP-OCU' => ['nombre' => 'Protección Ocular', 'categoria_epp' => 'Protección Ocular'],
            'EPP-AUD' => ['nombre' => 'Protección Auditiva', 'categoria_epp' => 'Protección Auditiva'],
            'EPP-RES' => ['nombre' => 'Protección Respiratoria', 'categoria_epp' => 'Protección Respiratoria'],
            'EPP-MAN' => ['nombre' => 'Protección de Manos', 'categoria_epp' => 'Protección de Manos'],
            'EPP-PIE' => ['nombre' => 'Protección de Pies', 'categoria_epp' => 'Protección de Pies'],
            'EPP-COR' => ['nombre' => 'Protección Corporal', 'categoria_epp' => 'Protección Corporal'],
            'EPP-ALT' => ['nombre' => 'Trabajo en Altura', 'categoria_epp' => 'Trabajo en Altura'],
        ];

        $familiasEpp = collect($familiasEpp)
            ->mapWithKeys(fn (array $data, string $codigo) => [
                $codigo => Familia::query()->updateOrCreate(
                    ['codigo' => $codigo],
                    [
                        'nombre' => $data['nombre'],
                        'descripcion' => 'Familia EPP - '.$data['nombre'],
                        'es_epp' => true,
                        'categoria_epp' => $data['categoria_epp'],
                        'activo' => true,
                    ],
                ),
            ]);

        $herramientas = Familia::query()->updateOrCreate(
            ['codigo' => 'HERR'],
            ['nombre' => 'Herramientas', 'descripcion' => 'Herramientas de uso operativo', 'es_epp' => false, 'activo' => true],
        );

        $epp = Familia::query()->updateOrCreate(
            ['codigo' => 'EPP'],
            ['nombre' => 'Equipos de Protección Personal', 'descripcion' => 'EPP para operaciones mineras', 'es_epp' => true, 'categoria_epp' => 'Otros', 'activo' => true],
        );

        $consumibles = Familia::query()->updateOrCreate(
            ['codigo' => 'CONS'],
            ['nombre' => 'Consumibles', 'descripcion' => 'Materiales consumibles de almacen', 'es_epp' => false, 'activo' => true],
        );

        $unidad = UnidadMedida::query()->updateOrCreate(
            ['codigo' => 'UND'],
            ['nombre' => 'Unidad', 'abreviatura' => 'und', 'activo' => true],
        );

        $par = UnidadMedida::query()->updateOrCreate(
            ['codigo' => 'PAR'],
            ['nombre' => 'Par', 'abreviatura' => 'par', 'activo' => true],
        );

        UnidadMedida::query()->updateOrCreate(
            ['codigo' => 'MTR'],
            ['nombre' => 'Metro', 'abreviatura' => 'm', 'activo' => true],
        );

        Producto::query()->updateOrCreate(
            ['codigo' => 'EPP-CAS-001'],
            [
                'familia_id' => ($familiasEpp['EPP-CAB'] ?? $epp)->id,
                'unidad_medida_id' => $unidad->id,
                'nombre' => 'Casco de seguridad',
                'descripcion' => 'Casco para operacion minera',
                'marca' => 'MSA',
                'stock_minimo' => 10,
                'stock_maximo' => 100,
                'costo_referencial' => 35,
                'es_epp' => true,
                'vida_util_dias' => 365,
                'dias_alerta_vencimiento' => 30,
                'requiere_talla' => false,
                'activo' => true,
            ],
        );

        Producto::query()->updateOrCreate(
            ['codigo' => 'EPP-GUA-001'],
            [
                'familia_id' => ($familiasEpp['EPP-MAN'] ?? $epp)->id,
                'unidad_medida_id' => $par->id,
                'nombre' => 'Guantes de cuero reforzado',
                'stock_minimo' => 20,
                'stock_maximo' => 200,
                'costo_referencial' => 18,
                'es_epp' => true,
                'vida_util_dias' => 90,
                'dias_alerta_vencimiento' => 15,
                'requiere_talla' => true,
                'tallas_disponibles' => ['S', 'M', 'L', 'XL'],
                'activo' => true,
            ],
        );

        Producto::query()->updateOrCreate(
            ['codigo' => 'HERR-PAL-001'],
            [
                'familia_id' => $herramientas->id,
                'unidad_medida_id' => $unidad->id,
                'nombre' => 'Pala minera',
                'stock_minimo' => 5,
                'stock_maximo' => 50,
                'costo_referencial' => 45,
                'es_epp' => false,
                'activo' => true,
            ],
        );

        Producto::query()->updateOrCreate(
            ['codigo' => 'CONS-TRA-001'],
            [
                'familia_id' => $consumibles->id,
                'unidad_medida_id' => $unidad->id,
                'nombre' => 'Trapo industrial',
                'stock_minimo' => 50,
                'stock_maximo' => 500,
                'costo_referencial' => 4.5,
                'es_epp' => false,
                'activo' => true,
            ],
        );
    }
}
