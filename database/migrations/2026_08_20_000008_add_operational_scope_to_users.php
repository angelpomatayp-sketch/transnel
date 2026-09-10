<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'almacen_id')) {
                $table->foreignId('almacen_id')->nullable()->after('email')->constrained('almacenes')->nullOnDelete();
            }

            if (! Schema::hasColumn('users', 'centro_costo_id')) {
                $table->foreignId('centro_costo_id')->nullable()->after('almacen_id')->constrained('centros_costos')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            foreach (['centro_costo_id', 'almacen_id'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
