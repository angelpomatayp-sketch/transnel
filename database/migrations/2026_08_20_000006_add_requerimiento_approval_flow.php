<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('requerimiento_historial')) {
            Schema::create('requerimiento_historial', function (Blueprint $table) {
                $table->id();
                $table->foreignId('requerimiento_id')->constrained('requerimientos')->cascadeOnDelete();
                $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('accion');
                $table->string('estado_anterior')->nullable();
                $table->string('estado_nuevo')->nullable();
                $table->text('comentario')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('ordenes_compra') && ! Schema::hasColumn('ordenes_compra', 'requerimiento_id')) {
            Schema::table('ordenes_compra', function (Blueprint $table) {
                $table->foreignId('requerimiento_id')->nullable()->after('numero')->constrained('requerimientos')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ordenes_compra') && Schema::hasColumn('ordenes_compra', 'requerimiento_id')) {
            Schema::table('ordenes_compra', function (Blueprint $table) {
                $table->dropConstrainedForeignId('requerimiento_id');
            });
        }

        Schema::dropIfExists('requerimiento_historial');
    }
};
