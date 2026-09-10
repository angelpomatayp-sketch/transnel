<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            if (! Schema::hasColumn('productos', 'es_epp')) {
                $table->boolean('es_epp')->default(false)->after('activo');
            }

            if (! Schema::hasColumn('productos', 'vida_util_dias')) {
                $table->unsignedInteger('vida_util_dias')->nullable()->after('es_epp');
            }

            if (! Schema::hasColumn('productos', 'dias_alerta')) {
                $table->unsignedInteger('dias_alerta')->nullable()->after('vida_util_dias');
            }

            if (! Schema::hasColumn('productos', 'requiere_talla')) {
                $table->boolean('requiere_talla')->default(false)->after('dias_alerta');
            }

            if (! Schema::hasColumn('productos', 'tallas')) {
                $table->string('tallas')->nullable()->after('requiere_talla');
            }

            if (! Schema::hasColumn('productos', 'imagenes')) {
                $table->json('imagenes')->nullable()->after('tallas');
            }
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            foreach (['imagenes', 'tallas', 'requiere_talla', 'dias_alerta', 'vida_util_dias', 'es_epp'] as $column) {
                if (Schema::hasColumn('productos', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
