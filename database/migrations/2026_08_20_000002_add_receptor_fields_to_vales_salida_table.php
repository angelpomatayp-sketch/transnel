<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vales_salida')) {
            return;
        }

        Schema::table('vales_salida', function (Blueprint $table) {
            if (! Schema::hasColumn('vales_salida', 'trabajador_id')) {
                $table->unsignedBigInteger('trabajador_id')->nullable()->after('solicitante_id');
            }

            if (! Schema::hasColumn('vales_salida', 'receptor_nombre')) {
                $table->string('receptor_nombre')->nullable()->after('trabajador_id');
            }

            if (! Schema::hasColumn('vales_salida', 'receptor_dni')) {
                $table->string('receptor_dni', 20)->nullable()->after('receptor_nombre');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('vales_salida')) {
            return;
        }

        Schema::table('vales_salida', function (Blueprint $table) {
            if (Schema::hasColumn('vales_salida', 'trabajador_id')) {
                $table->dropColumn('trabajador_id');
            }

            if (Schema::hasColumn('vales_salida', 'receptor_nombre')) {
                $table->dropColumn('receptor_nombre');
            }

            if (Schema::hasColumn('vales_salida', 'receptor_dni')) {
                $table->dropColumn('receptor_dni');
            }
        });
    }
};
