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
        Schema::create('movimientos', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 30)->unique();
            $table->string('tipo', 30)->index();
            $table->string('subtipo', 50)->nullable();
            $table->foreignId('almacen_origen_id')->nullable()->constrained('almacenes')->restrictOnDelete();
            $table->foreignId('almacen_destino_id')->nullable()->constrained('almacenes')->restrictOnDelete();
            $table->foreignId('centro_costo_id')->nullable()->constrained('centros_costos')->nullOnDelete();
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->date('fecha')->index();
            $table->string('documento')->nullable();
            $table->string('estado', 30)->default('confirmado')->index();
            $table->text('observaciones')->nullable();
            $table->timestamp('anulado_en')->nullable();
            $table->foreignId('anulado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->text('motivo_anulacion')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tipo', 'fecha']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimientos');
    }
};
