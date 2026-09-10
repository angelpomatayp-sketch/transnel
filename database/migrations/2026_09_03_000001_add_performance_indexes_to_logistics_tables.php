<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addIndex('kardex', ['almacen_id', 'fecha', 'id'], 'kardex_almacen_fecha_id_index');
        $this->addIndex('kardex', ['movimiento_id', 'fecha'], 'kardex_movimiento_fecha_index');
        $this->addIndex('stock_almacen', ['stock_minimo', 'stock_actual'], 'stock_almacen_minimo_actual_index');
        $this->addIndex('epp_asignaciones', ['estado', 'fecha_vencimiento'], 'epp_asignaciones_estado_vencimiento_index');
        $this->addIndex('epp_asignaciones', ['almacen_id', 'estado'], 'epp_asignaciones_almacen_estado_index');
        $this->addIndex('epp_asignaciones', ['centro_costo_id', 'estado'], 'epp_asignaciones_centro_estado_index');
        $this->addIndex('ordenes_compra', ['estado'], 'ordenes_compra_estado_index');
        $this->addIndex('ordenes_compra', ['almacen_id', 'estado'], 'ordenes_compra_almacen_estado_index');
        $this->addIndex('ordenes_compra', ['centro_costo_id', 'estado'], 'ordenes_compra_centro_estado_index');
        $this->addIndex('prestamos', ['estado'], 'prestamos_estado_index');
        $this->addIndex('prestamos', ['trabajador_id'], 'prestamos_trabajador_index');
        $this->addIndex('prestamos', ['almacen_id', 'estado'], 'prestamos_almacen_estado_index');
        $this->addIndex('prestamos', ['centro_costo_id', 'estado'], 'prestamos_centro_estado_index');
    }

    public function down(): void
    {
        $this->dropIndex('prestamos', 'prestamos_centro_estado_index');
        $this->dropIndex('prestamos', 'prestamos_almacen_estado_index');
        $this->dropIndex('prestamos', 'prestamos_trabajador_index');
        $this->dropIndex('prestamos', 'prestamos_estado_index');
        $this->dropIndex('ordenes_compra', 'ordenes_compra_centro_estado_index');
        $this->dropIndex('ordenes_compra', 'ordenes_compra_almacen_estado_index');
        $this->dropIndex('ordenes_compra', 'ordenes_compra_estado_index');
        $this->dropIndex('epp_asignaciones', 'epp_asignaciones_centro_estado_index');
        $this->dropIndex('epp_asignaciones', 'epp_asignaciones_almacen_estado_index');
        $this->dropIndex('epp_asignaciones', 'epp_asignaciones_estado_vencimiento_index');
        $this->dropIndex('stock_almacen', 'stock_almacen_minimo_actual_index');
        $this->dropIndex('kardex', 'kardex_movimiento_fecha_index');
        $this->dropIndex('kardex', 'kardex_almacen_fecha_id_index');
    }

    private function addIndex(string $table, array $columns, string $name): void
    {
        if (! Schema::hasTable($table) || Schema::hasIndex($table, $name)) {
            return;
        }

        $missingColumn = collect($columns)->first(fn ($column) => ! Schema::hasColumn($table, $column));

        if ($missingColumn) {
            return;
        }

        Schema::table($table, fn (Blueprint $table) => $table->index($columns, $name));
    }

    private function dropIndex(string $table, string $name): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasIndex($table, $name)) {
            return;
        }

        Schema::table($table, fn (Blueprint $table) => $table->dropIndex($name));
    }
};
