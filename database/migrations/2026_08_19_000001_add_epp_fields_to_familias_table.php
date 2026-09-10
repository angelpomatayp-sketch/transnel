<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('familias', function (Blueprint $table) {
            if (! Schema::hasColumn('familias', 'es_epp')) {
                $table->boolean('es_epp')->default(false)->after('activo');
            }

            if (! Schema::hasColumn('familias', 'categoria_epp')) {
                $table->string('categoria_epp', 50)->nullable()->after('es_epp');
            }
        });
    }

    public function down(): void
    {
        Schema::table('familias', function (Blueprint $table) {
            if (Schema::hasColumn('familias', 'categoria_epp')) {
                $table->dropColumn('categoria_epp');
            }

            if (Schema::hasColumn('familias', 'es_epp')) {
                $table->dropColumn('es_epp');
            }
        });
    }
};
