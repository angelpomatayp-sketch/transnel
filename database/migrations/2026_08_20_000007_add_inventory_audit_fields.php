<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('movimientos')) {
            Schema::table('movimientos', function (Blueprint $table) {
                if (! Schema::hasColumn('movimientos', 'motivo_anulacion')) {
                    $table->text('motivo_anulacion')->nullable()->after('estado');
                }

                if (! Schema::hasColumn('movimientos', 'anulado_por')) {
                    $table->foreignId('anulado_por')->nullable()->after('motivo_anulacion')->constrained('users')->nullOnDelete();
                }

                if (! Schema::hasColumn('movimientos', 'fecha_anulacion')) {
                    $table->timestamp('fecha_anulacion')->nullable()->after('anulado_por');
                }

                if (! Schema::hasColumn('movimientos', 'movimiento_reversa_id')) {
                    $table->unsignedBigInteger('movimiento_reversa_id')->nullable()->after('fecha_anulacion');
                }
            });
        }

        if (! Schema::hasTable('inventario_auditorias')) {
            Schema::create('inventario_auditorias', function (Blueprint $table) {
                $table->id();
                $table->foreignId('movimiento_id')->nullable()->constrained('movimientos')->nullOnDelete();
                $table->foreignId('producto_id')->nullable()->constrained('productos')->nullOnDelete();
                $table->foreignId('almacen_id')->nullable()->constrained('almacenes')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('accion', 80);
                $table->string('tipo', 80)->nullable();
                $table->decimal('cantidad', 14, 4)->default(0);
                $table->decimal('stock_antes', 14, 4)->nullable();
                $table->decimal('stock_despues', 14, 4)->nullable();
                $table->decimal('costo_promedio', 14, 4)->nullable();
                $table->string('documento')->nullable();
                $table->text('motivo')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventario_auditorias');

        if (Schema::hasTable('movimientos')) {
            Schema::table('movimientos', function (Blueprint $table) {
                foreach (['movimiento_reversa_id', 'fecha_anulacion', 'anulado_por', 'motivo_anulacion'] as $column) {
                    if (Schema::hasColumn('movimientos', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
