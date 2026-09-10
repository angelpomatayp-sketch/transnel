<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('centro_costo_id')->nullable()->after('telefono')->constrained('centros_costos')->nullOnDelete();
            $table->foreignId('almacen_id')->nullable()->after('centro_costo_id')->constrained('almacenes')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('almacen_id');
            $table->dropConstrainedForeignId('centro_costo_id');
        });
    }
};
