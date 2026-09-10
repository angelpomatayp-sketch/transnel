<?php

namespace App\Services;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class MovimientoService
{
    public function registrarEntrada(array $data, int $userId): int
    {
        $data['tipo'] = $data['tipo'] ?? 'entrada';

        return $this->registrar($data, $userId);
    }

    public function registrarSalida(array $data, int $userId): int
    {
        $data['tipo'] = $data['tipo'] ?? 'salida';

        return $this->registrar($data, $userId);
    }

    public function registrarPrestamo(array $data, int $userId): int
    {
        $data['tipo'] = 'prestamo';
        $data['observaciones'] = $data['observaciones'] ?? 'Prestamo de herramienta/equipo';

        return $this->registrar($data, $userId);
    }

    public function registrarDevolucionPrestamo(array $data, int $userId): int
    {
        $data['tipo'] = 'devolucion_prestamo';
        $data['observaciones'] = $data['observaciones'] ?? 'Devolucion de herramienta/equipo prestado';

        return $this->registrar($data, $userId);
    }

    public function registrarEntregaEpp(array $data, int $userId): int
    {
        $data['tipo'] = 'epp';
        $data['observaciones'] = $data['observaciones'] ?? 'Entrega de EPP';

        return $this->registrar($data, $userId);
    }

    public function registrarAjuste(array $data, int $userId): int
    {
        $tipo = strtolower((string) ($data['tipo'] ?? 'ajuste_entrada'));
        $data['tipo'] = in_array($tipo, ['ajuste_entrada', 'ajuste_salida'], true) ? $tipo : 'ajuste_entrada';

        return $this->registrar($data, $userId);
    }

    public function anularMovimiento(int $movimientoId, string $motivo, int $userId): void
    {
        $this->anular($movimientoId, $motivo, $userId);
    }

    public function registrar(array $data, int $userId): int
    {
        $data = app(AccessControlService::class)->forceAssignedOperation($data);
        $tipo = strtolower((string) ($data['tipo'] ?? 'salida'));
        $items = collect($data['items'] ?? $data['detalles'] ?? [])
            ->filter(fn ($item) => ! empty($item['producto_id']))
            ->values();

        if ($items->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'Debe agregar al menos un producto.',
            ]);
        }

        $this->validarCabecera($tipo, $data);

        return DB::transaction(function () use ($data, $userId, $tipo, $items) {
            foreach ($items as $item) {
                $this->validarItem($tipo, $item);
            }

            $numero = $data['numero'] ?? $this->generarNumero($tipo);
            $movimientoId = DB::table('movimientos')->insertGetId($this->onlyExistingColumns('movimientos', [
                'numero' => $numero,
                'tipo' => $tipo,
                'subtipo' => $data['subtipo'] ?? null,
                'almacen_origen_id' => $data['almacen_origen_id'] ?? null,
                'almacen_destino_id' => $data['almacen_destino_id'] ?? null,
                'centro_costo_id' => $data['centro_costo_id'] ?? null,
                'fecha' => $data['fecha'] ?? now()->toDateString(),
                'documento' => $data['documento'] ?? $numero,
                'observaciones' => $data['observaciones'] ?? null,
                'motivo' => $data['motivo'] ?? $data['observaciones'] ?? null,
                'usuario_id' => $userId,
                'user_id' => $userId,
                'estado' => 'confirmado',
                'created_at' => now(),
                'updated_at' => now(),
            ]));

            foreach ($items as $item) {
                $productoId = (int) $item['producto_id'];
                $cantidad = $this->decimal($item['cantidad'] ?? $item['cantidad_solicitada'] ?? 0);
                $costoUnitario = $this->decimal($item['costo_unitario'] ?? $item['costo'] ?? 0);

                $detalleId = $this->crearDetalle($movimientoId, $item, $cantidad, $costoUnitario);
                $almacenAuditoria = $this->almacenParaTipo($tipo, $data);
                $stockAntes = $this->stockDisponible($productoId, $almacenAuditoria);

                $this->aplicarInventario($tipo, $data, $productoId, $cantidad, $costoUnitario);

                $stockDespues = $this->stockDisponible($productoId, $almacenAuditoria);
                $this->registrarKardex($movimientoId, $detalleId, $tipo, $data, $productoId, $cantidad, $costoUnitario);
                $this->registrarAuditoria($movimientoId, $productoId, $almacenAuditoria, $userId, 'registrar_movimiento', $tipo, $cantidad, $stockAntes, $stockDespues, $data);
            }

            return $movimientoId;
        });
    }

    public function anular(int $movimientoId, string $motivo, int $userId): void
    {
        if (trim($motivo) === '') {
            throw ValidationException::withMessages([
                'motivo' => 'Debe indicar el motivo de anulacion.',
            ]);
        }

        DB::transaction(function () use ($movimientoId, $motivo, $userId) {
            $movimiento = DB::table('movimientos')->where('id', $movimientoId)->lockForUpdate()->first();

            if (! $movimiento) {
                throw ValidationException::withMessages([
                    'movimiento' => 'Movimiento no encontrado.',
                ]);
            }

            if (($movimiento->estado ?? null) === 'anulado') {
                throw ValidationException::withMessages([
                    'movimiento' => 'El movimiento ya esta anulado.',
                ]);
            }

            $detalles = DB::table($this->detalleTable())
                ->where('movimiento_id', $movimientoId)
                ->get();

            foreach ($detalles as $detalle) {
                $data = [
                    'almacen_origen_id' => $movimiento->almacen_origen_id ?? null,
                    'almacen_destino_id' => $movimiento->almacen_destino_id ?? null,
                    'fecha' => now()->toDateString(),
                    'documento' => $movimiento->documento ?? $movimiento->numero ?? null,
                ];

                $tipoReversa = $this->tipoReversa((string) ($movimiento->tipo ?? 'salida'));
                $cantidad = $this->decimal($detalle->cantidad ?? $detalle->cantidad_solicitada ?? 0);
                $costo = $this->decimal($detalle->costo_unitario ?? $detalle->costo ?? 0);

                $productoId = (int) $detalle->producto_id;
                $almacenAuditoria = $this->almacenParaTipo($tipoReversa, $data);
                $stockAntes = $this->stockDisponible($productoId, $almacenAuditoria);

                $this->aplicarInventario($tipoReversa, $data, $productoId, $cantidad, $costo, true);

                $stockDespues = $this->stockDisponible($productoId, $almacenAuditoria);
                $this->registrarKardex($movimientoId, $detalle->id ?? null, $tipoReversa, $data, $productoId, $cantidad, $costo, true);
                $this->registrarAuditoria($movimientoId, $productoId, $almacenAuditoria, $userId, 'anular_movimiento', $tipoReversa, $cantidad, $stockAntes, $stockDespues, array_merge($data, ['motivo' => $motivo]));
            }

            DB::table('movimientos')->where('id', $movimientoId)->update($this->onlyExistingColumns('movimientos', [
                'estado' => 'anulado',
                'motivo_anulacion' => $motivo,
                'anulado_por' => $userId,
                'fecha_anulacion' => now(),
                'updated_at' => now(),
            ]));
        });
    }

    public function stockDisponible(int $productoId, ?int $almacenId): float
    {
        if (! $almacenId) {
            return 0.0;
        }

        $row = $this->stockRow($productoId, $almacenId, true);

        return $row ? $this->decimal($row->{$this->stockQtyColumn()} ?? 0) : 0.0;
    }

    private function validarCabecera(string $tipo, array $data): void
    {
        if (in_array($tipo, ['salida', 'ajuste_salida', 'prestamo', 'epp'], true) && empty($data['almacen_origen_id'])) {
            throw ValidationException::withMessages([
                'almacen_origen_id' => 'Debe seleccionar el almacen de salida.',
            ]);
        }

        if (in_array($tipo, ['entrada', 'ajuste_entrada', 'compra'], true) && empty($data['almacen_destino_id'])) {
            throw ValidationException::withMessages([
                'almacen_destino_id' => 'Debe seleccionar el almacen de entrada.',
            ]);
        }

        if (str_starts_with($tipo, 'ajuste') && trim((string) ($data['motivo'] ?? $data['observaciones'] ?? '')) === '') {
            throw ValidationException::withMessages([
                'motivo' => 'Debe registrar el motivo del reajuste.',
            ]);
        }
    }

    private function validarItem(string $tipo, array $item): void
    {
        $cantidad = $this->decimal($item['cantidad'] ?? $item['cantidad_solicitada'] ?? 0);

        if ($cantidad <= 0) {
            throw ValidationException::withMessages([
                'cantidad' => 'La cantidad debe ser mayor a cero.',
            ]);
        }

        if (in_array($tipo, ['entrada', 'compra'], true)) {
            $costo = $this->decimal($item['costo_unitario'] ?? $item['costo'] ?? 0);
            if ($costo <= 0) {
                throw ValidationException::withMessages([
                    'costo_unitario' => 'El costo unitario debe ser mayor a cero para entradas o compras.',
                ]);
            }
        }
    }

    private function aplicarInventario(string $tipo, array $data, int $productoId, float $cantidad, float $costoUnitario, bool $esReversa = false): void
    {
        if (in_array($tipo, ['entrada', 'compra', 'ajuste_entrada', 'devolucion_prestamo'], true)) {
            $this->incrementarStock($productoId, (int) ($data['almacen_destino_id'] ?? $data['almacen_origen_id']), $cantidad, $costoUnitario);
            return;
        }

        if (in_array($tipo, ['salida', 'prestamo', 'epp', 'ajuste_salida'], true)) {
            $permitirNegativo = (bool) ($data['permitir_negativo'] ?? false);
            $this->descontarStock($productoId, (int) ($data['almacen_origen_id'] ?? $data['almacen_destino_id']), $cantidad, $permitirNegativo || $esReversa);
            return;
        }

        if ($tipo === 'transferencia') {
            $this->descontarStock($productoId, (int) ($data['almacen_origen_id'] ?? 0), $cantidad, false);
            $this->incrementarStock($productoId, (int) ($data['almacen_destino_id'] ?? 0), $cantidad, $costoUnitario);
        }
    }

    private function incrementarStock(int $productoId, int $almacenId, float $cantidad, float $costoUnitario): void
    {
        if (! $almacenId) {
            throw ValidationException::withMessages(['almacen' => 'Debe seleccionar un almacen.']);
        }

        $row = $this->stockRow($productoId, $almacenId, true);
        $qtyColumn = $this->stockQtyColumn();
        $costColumn = $this->stockCostColumn();
        $actual = $row ? $this->decimal($row->{$qtyColumn} ?? 0) : 0.0;
        $costoActual = $row && $costColumn ? $this->decimal($row->{$costColumn} ?? 0) : 0.0;
        $nuevo = $actual + $cantidad;
        $nuevoCosto = $costColumn
            ? (($actual * $costoActual) + ($cantidad * $costoUnitario)) / max($nuevo, 1)
            : null;

        $this->upsertStock($productoId, $almacenId, $nuevo, $nuevoCosto);
    }

    private function descontarStock(int $productoId, int $almacenId, float $cantidad, bool $permitirNegativo): void
    {
        if (! $almacenId) {
            throw ValidationException::withMessages(['almacen' => 'Debe seleccionar un almacen.']);
        }

        $row = $this->stockRow($productoId, $almacenId, true);
        $actual = $row ? $this->decimal($row->{$this->stockQtyColumn()} ?? 0) : 0.0;

        if (! $permitirNegativo && $actual < $cantidad) {
            $producto = Schema::hasTable('productos')
                ? DB::table('productos')->where('id', $productoId)->value('nombre')
                : "ID {$productoId}";

            throw ValidationException::withMessages([
                'stock' => "Stock insuficiente para {$producto}. Disponible: ".number_format($actual, 2).", requerido: ".number_format($cantidad, 2).'.',
            ]);
        }

        $this->upsertStock($productoId, $almacenId, $actual - $cantidad, null, false);
    }

    private function crearDetalle(int $movimientoId, array $item, float $cantidad, float $costoUnitario): ?int
    {
        $table = $this->detalleTable();

        if (! Schema::hasTable($table)) {
            return null;
        }

        return DB::table($table)->insertGetId($this->onlyExistingColumns($table, [
            'movimiento_id' => $movimientoId,
            'producto_id' => $item['producto_id'],
            'cantidad' => $cantidad,
            'cantidad_solicitada' => $cantidad,
            'cantidad_entregada' => $cantidad,
            'costo_unitario' => $costoUnitario,
            'costo' => $costoUnitario,
            'lote' => $item['lote'] ?? null,
            'fecha_vencimiento' => $item['fecha_vencimiento'] ?? $item['vencimiento'] ?? null,
            'observaciones' => $item['observaciones'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }

    private function registrarKardex(?int $movimientoId, ?int $detalleId, string $tipo, array $data, int $productoId, float $cantidad, float $costoUnitario, bool $esReversa = false): void
    {
        if (! Schema::hasTable('kardex')) {
            return;
        }

        $almacenId = $this->almacenParaTipo($tipo, $data);

        $stock = $this->stockDisponible($productoId, $almacenId ? (int) $almacenId : null);
        $costoPromedio = $this->costoPromedio($productoId, $almacenId ? (int) $almacenId : null) ?: $costoUnitario;
        $isEntrada = in_array($tipo, ['entrada', 'compra', 'ajuste_entrada', 'devolucion_prestamo'], true);
        $isSalida = in_array($tipo, ['salida', 'prestamo', 'epp', 'ajuste_salida'], true);
        $total = round($cantidad * $costoPromedio, 4);

        DB::table('kardex')->insert($this->onlyExistingColumns('kardex', [
            'movimiento_id' => $movimientoId,
            'movimiento_detalle_id' => $detalleId,
            'producto_id' => $productoId,
            'almacen_id' => $almacenId,
            'fecha' => $data['fecha'] ?? now()->toDateString(),
            'tipo' => $esReversa ? 'reversa_'.$tipo : $tipo,
            'documento' => $data['documento'] ?? null,
            'entrada' => $isEntrada ? $cantidad : 0,
            'salida' => $isSalida ? $cantidad : 0,
            'cantidad' => $cantidad,
            'costo_unitario' => $costoPromedio,
            'costo_total' => $total,
            'cantidad_entrada' => $isEntrada ? $cantidad : 0,
            'costo_entrada' => $isEntrada ? $costoPromedio : 0,
            'total_entrada' => $isEntrada ? $total : 0,
            'cantidad_salida' => $isSalida ? $cantidad : 0,
            'costo_salida' => $isSalida ? $costoPromedio : 0,
            'total_salida' => $isSalida ? $total : 0,
            'saldo_cantidad' => $stock,
            'saldo_valor' => $stock * $costoPromedio,
            'saldo_costo_promedio' => $costoPromedio,
            'saldo_total' => round($stock * $costoPromedio, 4),
            'observaciones' => $data['observaciones'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }

    private function registrarAuditoria(
        ?int $movimientoId,
        int $productoId,
        ?int $almacenId,
        int $userId,
        string $accion,
        string $tipo,
        float $cantidad,
        float $stockAntes,
        float $stockDespues,
        array $data
    ): void {
        if (! Schema::hasTable('inventario_auditorias')) {
            return;
        }

        DB::table('inventario_auditorias')->insert($this->onlyExistingColumns('inventario_auditorias', [
            'movimiento_id' => $movimientoId,
            'producto_id' => $productoId,
            'almacen_id' => $almacenId,
            'user_id' => $userId,
            'accion' => $accion,
            'tipo' => $tipo,
            'cantidad' => $cantidad,
            'stock_antes' => $stockAntes,
            'stock_despues' => $stockDespues,
            'costo_promedio' => $this->costoPromedio($productoId, $almacenId),
            'documento' => $data['documento'] ?? null,
            'motivo' => $data['motivo'] ?? $data['observaciones'] ?? null,
            'metadata' => json_encode([
                'almacen_origen_id' => $data['almacen_origen_id'] ?? null,
                'almacen_destino_id' => $data['almacen_destino_id'] ?? null,
                'centro_costo_id' => $data['centro_costo_id'] ?? null,
                'fecha' => $data['fecha'] ?? null,
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }

    private function stockRow(int $productoId, int $almacenId, bool $lock = false): ?object
    {
        if (! Schema::hasTable($this->stockTable())) {
            return null;
        }

        $query = DB::table($this->stockTable())
            ->where('producto_id', $productoId)
            ->where('almacen_id', $almacenId);

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    private function almacenParaTipo(string $tipo, array $data): ?int
    {
        return in_array($tipo, ['entrada', 'compra', 'ajuste_entrada', 'devolucion_prestamo'], true)
            ? (isset($data['almacen_destino_id']) ? (int) $data['almacen_destino_id'] : (isset($data['almacen_origen_id']) ? (int) $data['almacen_origen_id'] : null))
            : (isset($data['almacen_origen_id']) ? (int) $data['almacen_origen_id'] : (isset($data['almacen_destino_id']) ? (int) $data['almacen_destino_id'] : null));
    }

    private function costoPromedio(int $productoId, ?int $almacenId): float
    {
        if (! $almacenId) {
            return 0.0;
        }

        $costColumn = $this->stockCostColumn();

        if (! $costColumn) {
            return 0.0;
        }

        $row = $this->stockRow($productoId, $almacenId);

        return $row ? $this->decimal($row->{$costColumn} ?? 0) : 0.0;
    }

    private function upsertStock(int $productoId, int $almacenId, float $cantidad, ?float $costoPromedio, bool $updateCost = true): void
    {
        $table = $this->stockTable();

        if (! Schema::hasTable($table)) {
            return;
        }

        $row = $this->stockRow($productoId, $almacenId);
        $payload = [
            'producto_id' => $productoId,
            'almacen_id' => $almacenId,
            $this->stockQtyColumn() => $cantidad,
            'updated_at' => now(),
        ];

        $costColumn = $this->stockCostColumn();
        if ($updateCost && $costColumn && $costoPromedio !== null) {
            $payload[$costColumn] = $costoPromedio;
        }

        $payload = $this->onlyExistingColumns($table, $payload);

        if ($row) {
            DB::table($table)->where('id', $row->id)->update($payload);
            return;
        }

        $payload = $this->onlyExistingColumns($table, array_merge($payload, [
            'created_at' => now(),
        ]));

        try {
            DB::table($table)->insert($payload);
        } catch (QueryException $exception) {
            if ((string) $exception->getCode() !== '23000') {
                throw $exception;
            }

            DB::table($table)
                ->where('producto_id', $productoId)
                ->where('almacen_id', $almacenId)
                ->update($payload);
        }
    }

    private function stockTable(): string
    {
        foreach (['stock_almacen', 'stocks', 'inventarios', 'inventario_stocks'] as $table) {
            if (Schema::hasTable($table)) {
                return $table;
            }
        }

        return 'stocks';
    }

    private function stockQtyColumn(): string
    {
        foreach (['cantidad', 'existencia', 'stock_actual', 'stock'] as $column) {
            if (Schema::hasColumn($this->stockTable(), $column)) {
                return $column;
            }
        }

        return 'cantidad';
    }

    private function stockCostColumn(): ?string
    {
        foreach (['costo_promedio', 'costo_unitario', 'costo'] as $column) {
            if (Schema::hasColumn($this->stockTable(), $column)) {
                return $column;
            }
        }

        return null;
    }

    private function detalleTable(): string
    {
        foreach (['movimientos_detalle', 'movimiento_detalles', 'movimientos_detalles'] as $table) {
            if (Schema::hasTable($table)) {
                return $table;
            }
        }

        return 'movimiento_detalles';
    }

    private function tipoReversa(string $tipo): string
    {
        return match (strtolower($tipo)) {
            'entrada', 'compra', 'ajuste_entrada' => 'salida',
            'transferencia' => 'transferencia',
            default => 'entrada',
        };
    }

    private function generarNumero(string $tipo): string
    {
        $prefix = match ($tipo) {
            'entrada', 'compra', 'ajuste_entrada', 'devolucion_prestamo' => 'ENT',
            'transferencia' => 'TRA',
            default => 'SAL',
        };

        if (! Schema::hasTable('movimientos') || ! Schema::hasColumn('movimientos', 'numero')) {
            return $prefix.'-'.now()->format('YmdHis');
        }

        $year = now()->format('Y');
        $count = DB::table('movimientos')
            ->where('numero', 'like', "{$prefix}-{$year}-%")
            ->lockForUpdate()
            ->count() + 1;

        return sprintf('%s-%s-%06d', $prefix, $year, $count);
    }

    private function onlyExistingColumns(string $table, array $payload): array
    {
        if (! Schema::hasTable($table)) {
            return $payload;
        }

        return collect($payload)
            ->filter(fn ($value, $column) => Schema::hasColumn($table, $column))
            ->all();
    }

    private function decimal(mixed $value): float
    {
        return (float) str_replace(',', '.', (string) ($value ?? 0));
    }
}
