<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $familias = [
        'EPP-CAB' => ['nombre' => 'Protección de Cabeza', 'categoria_epp' => 'Protección de Cabeza'],
        'EPP-OCU' => ['nombre' => 'Protección Ocular', 'categoria_epp' => 'Protección Ocular'],
        'EPP-AUD' => ['nombre' => 'Protección Auditiva', 'categoria_epp' => 'Protección Auditiva'],
        'EPP-RES' => ['nombre' => 'Protección Respiratoria', 'categoria_epp' => 'Protección Respiratoria'],
        'EPP-MAN' => ['nombre' => 'Protección de Manos', 'categoria_epp' => 'Protección de Manos'],
        'EPP-PIE' => ['nombre' => 'Protección de Pies', 'categoria_epp' => 'Protección de Pies'],
        'EPP-COR' => ['nombre' => 'Protección Corporal', 'categoria_epp' => 'Protección Corporal'],
        'EPP-ALT' => ['nombre' => 'Trabajo en Altura', 'categoria_epp' => 'Trabajo en Altura'],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('familias')) {
            return;
        }

        foreach ($this->familias as $codigo => $data) {
            DB::table('familias')->updateOrInsert(
                ['codigo' => $codigo],
                [
                    'nombre' => $data['nombre'],
                    'descripcion' => 'Familia EPP - '.$data['nombre'],
                    'es_epp' => true,
                    'categoria_epp' => $data['categoria_epp'],
                    'activo' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }

        DB::table('familias')
            ->where('codigo', 'EPP')
            ->update([
                'nombre' => 'Equipos de Protección Personal',
                'categoria_epp' => 'Otros',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        //
    }
};
