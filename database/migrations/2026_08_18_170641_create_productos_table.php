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
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('familia_id')->constrained('familias')->restrictOnDelete();
            $table->foreignId('unidad_medida_id')->constrained('unidades_medida')->restrictOnDelete();
            $table->string('codigo', 50)->unique();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->string('marca')->nullable();
            $table->string('modelo')->nullable();
            $table->string('ubicacion')->nullable();
            $table->string('lote')->nullable();
            $table->decimal('stock_minimo', 14, 4)->default(0);
            $table->decimal('stock_maximo', 14, 4)->nullable();
            $table->decimal('costo_referencial', 14, 4)->nullable();
            $table->boolean('es_epp')->default(false)->index();
            $table->unsignedInteger('vida_util_dias')->nullable();
            $table->unsignedInteger('dias_alerta_vencimiento')->nullable();
            $table->boolean('requiere_talla')->default(false);
            $table->json('tallas_disponibles')->nullable();
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
        Schema::dropIfExists('productos');
    }
};
