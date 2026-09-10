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
        Schema::create('trabajadores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('centro_costo_id')->nullable()->constrained('centros_costos')->nullOnDelete();
            $table->string('nombre');
            $table->string('dni', 20)->unique();
            $table->string('cargo')->nullable();
            $table->string('telefono', 30)->nullable();
            $table->date('fecha_ingreso')->nullable();
            $table->boolean('activo')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trabajadores');
    }
};
