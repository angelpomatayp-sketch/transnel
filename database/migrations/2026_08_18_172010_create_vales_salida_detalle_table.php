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
        Schema::create('vale_salida_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vale_salida_id')->constrained('vales_salida')->cascadeOnDelete();
            $table->foreignId('requerimiento_detalle_id')->nullable()->constrained('requerimientos_detalle')->nullOnDelete();
            $table->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $table->decimal('cantidad', 14, 4);
            $table->decimal('costo_unitario', 14, 4)->default(0);
            $table->decimal('costo_total', 14, 4)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vale_salida_detalles');
    }
};
