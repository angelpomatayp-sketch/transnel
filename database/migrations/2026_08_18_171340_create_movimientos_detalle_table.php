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
        Schema::create('movimientos_detalle', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movimiento_id')->constrained('movimientos')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $table->decimal('cantidad', 14, 4);
            $table->decimal('costo_unitario', 14, 4)->default(0);
            $table->decimal('costo_total', 14, 4)->default(0);
            $table->string('lote')->nullable();
            $table->date('vencimiento')->nullable();
            $table->timestamps();
            $table->index(['producto_id', 'movimiento_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimientos_detalle');
    }
};
