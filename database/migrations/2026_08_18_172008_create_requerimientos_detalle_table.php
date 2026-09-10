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
        Schema::create('requerimientos_detalle', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requerimiento_id')->constrained('requerimientos')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $table->decimal('cantidad_solicitada', 14, 4);
            $table->decimal('cantidad_aprobada', 14, 4)->default(0);
            $table->decimal('cantidad_entregada', 14, 4)->default(0);
            $table->text('especificaciones')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requerimientos_detalle');
    }
};
