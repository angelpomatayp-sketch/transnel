<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('epp_asignaciones')) {
            return;
        }

        Schema::create('epp_asignaciones', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->foreignId('trabajador_id')->constrained('trabajadores')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $table->foreignId('almacen_id')->constrained('almacenes')->restrictOnDelete();
            $table->foreignId('centro_costo_id')->nullable()->constrained('centros_costos')->nullOnDelete();
            $table->foreignId('movimiento_id')->nullable()->constrained('movimientos')->nullOnDelete();
            $table->decimal('cantidad', 12, 4)->default(1);
            $table->string('talla', 50)->nullable();
            $table->date('fecha_entrega');
            $table->date('fecha_vencimiento')->nullable();
            $table->string('estado', 30)->default('entregado');
            $table->text('observaciones')->nullable();
            $table->timestamp('fecha_devolucion')->nullable();
            $table->text('motivo_devolucion')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('epp_asignaciones');
    }
};
