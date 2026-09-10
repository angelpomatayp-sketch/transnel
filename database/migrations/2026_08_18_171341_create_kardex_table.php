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
        Schema::create('kardex', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $table->foreignId('almacen_id')->constrained('almacenes')->restrictOnDelete();
            $table->foreignId('movimiento_id')->constrained('movimientos')->cascadeOnDelete();
            $table->date('fecha')->index();
            $table->string('tipo', 30)->index();
            $table->decimal('cantidad_entrada', 14, 4)->default(0);
            $table->decimal('costo_entrada', 14, 4)->default(0);
            $table->decimal('total_entrada', 14, 4)->default(0);
            $table->decimal('cantidad_salida', 14, 4)->default(0);
            $table->decimal('costo_salida', 14, 4)->default(0);
            $table->decimal('total_salida', 14, 4)->default(0);
            $table->decimal('saldo_cantidad', 14, 4)->default(0);
            $table->decimal('saldo_costo_promedio', 14, 4)->default(0);
            $table->decimal('saldo_total', 14, 4)->default(0);
            $table->timestamps();
            $table->index(['producto_id', 'almacen_id', 'fecha']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kardex');
    }
};
