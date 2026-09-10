<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('proveedores')) {
            Schema::create('proveedores', function (Blueprint $table) {
                $table->id();
                $table->string('ruc', 20)->nullable()->unique();
                $table->string('razon_social');
                $table->string('nombre_comercial')->nullable();
                $table->string('contacto')->nullable();
                $table->string('telefono', 40)->nullable();
                $table->string('email')->nullable();
                $table->string('direccion')->nullable();
                $table->boolean('activo')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('ordenes_compra')) {
            Schema::create('ordenes_compra', function (Blueprint $table) {
                $table->id();
                $table->string('numero')->unique();
                $table->foreignId('proveedor_id')->nullable()->constrained('proveedores')->nullOnDelete();
                $table->foreignId('almacen_id')->nullable()->constrained('almacenes')->nullOnDelete();
                $table->foreignId('centro_costo_id')->nullable()->constrained('centros_costos')->nullOnDelete();
                $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
                $table->date('fecha');
                $table->date('fecha_entrega')->nullable();
                $table->string('estado')->default('pendiente');
                $table->string('documento_referencia')->nullable();
                $table->text('observaciones')->nullable();
                $table->decimal('subtotal', 14, 2)->default(0);
                $table->decimal('igv', 14, 2)->default(0);
                $table->decimal('total', 14, 2)->default(0);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('orden_compra_detalles')) {
            Schema::create('orden_compra_detalles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('orden_compra_id')->constrained('ordenes_compra')->cascadeOnDelete();
                $table->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
                $table->decimal('cantidad', 12, 4);
                $table->decimal('cantidad_recibida', 12, 4)->default(0);
                $table->decimal('precio_unitario', 14, 4)->default(0);
                $table->decimal('subtotal', 14, 2)->default(0);
                $table->text('observaciones')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('orden_compra_detalles');
        Schema::dropIfExists('ordenes_compra');
        Schema::dropIfExists('proveedores');
    }
};
