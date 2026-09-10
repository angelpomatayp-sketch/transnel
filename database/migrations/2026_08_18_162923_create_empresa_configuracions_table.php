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
        Schema::create('empresa_configuracion', function (Blueprint $table) {
            $table->id();
            $table->string('razon_social');
            $table->string('nombre_comercial')->nullable();
            $table->string('ruc', 11)->unique();
            $table->string('direccion')->nullable();
            $table->string('ciudad')->default('Lima');
            $table->string('pais')->default('Peru');
            $table->string('telefono', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('rubro')->default('Servicios logisticos y contratistas para mineria');
            $table->string('moneda', 3)->default('PEN');
            $table->string('metodo_valorizacion')->default('promedio_ponderado');
            $table->boolean('bloquear_stock_negativo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empresa_configuracion');
    }
};
