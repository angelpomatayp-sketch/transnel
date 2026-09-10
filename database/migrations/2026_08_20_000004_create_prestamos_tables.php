<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('prestamos')) {
            Schema::create('prestamos', function (Blueprint $table) {
                $table->id();
                $table->string('codigo')->unique();
                $table->foreignId('trabajador_id')->constrained('trabajadores')->restrictOnDelete();
                $table->foreignId('almacen_id')->constrained('almacenes')->restrictOnDelete();
                $table->foreignId('centro_costo_id')->nullable()->constrained('centros_costos')->nullOnDelete();
                $table->foreignId('movimiento_salida_id')->nullable()->constrained('movimientos')->nullOnDelete();
                $table->foreignId('movimiento_entrada_id')->nullable()->constrained('movimientos')->nullOnDelete();
                $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
                $table->date('fecha_prestamo');
                $table->date('fecha_devolucion_programada')->nullable();
                $table->timestamp('fecha_devolucion')->nullable();
                $table->string('estado', 30)->default('prestado');
                $table->text('observaciones')->nullable();
                $table->text('motivo_devolucion')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('prestamo_detalles')) {
            Schema::create('prestamo_detalles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('prestamo_id')->constrained('prestamos')->cascadeOnDelete();
                $table->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
                $table->decimal('cantidad', 12, 4)->default(1);
                $table->decimal('cantidad_devuelta', 12, 4)->default(0);
                $table->text('observaciones')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('prestamo_detalles');
        Schema::dropIfExists('prestamos');
    }
};
