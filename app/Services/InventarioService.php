<?php

namespace App\Services;

use App\Models\Kardex;
use App\Models\Movimiento;
use App\Models\Producto;
use App\Models\StockAlmacen;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventarioService
{
    public function registrarMovimiento(array $data): Movimiento
    {
        return DB::transaction(function () use ($data) {
            $movimiento = Movimiento::query()->create([
                'numero' => $this->generarNumero($data['tipo']),
                'tipo' => $data['tipo'],
                'subtipo' => $data['subtipo'] ?? null,
                'almacen_origen_id' => $data['almacen_origen_id'] ?? null,
                'almacen_destino_id' => $data['almacen_destino_id'] ?? null,
                'centro_costo_id' => $data['centro_costo_id'] ?? null,
                'usuario_id' => $data['usuario_id'],
                'fecha' => $data['fecha'],
                'documento' => $data['documento'] ?? null,
                'estado' => 'confirmado',
                'observaciones' => $data['observaciones'] ?? null,
            ]);

            foreach ($data['detalles'] as $detalle) {
                $producto = Producto::query()->findOrFail($detalle['producto_id']);
                $cantidad = round((float) $detalle['cantidad'], 4);
                $costoUnitario = round((float) ($detalle['costo_unitario'] ?? 0), 4);

                if ($cantidad <= 0) {
                    throw ValidationException::withMessages([
                        'detalles' => 'La cantidad debe ser mayor a cero.',
                    ]);
                }

                if (in_array($data['tipo'], ['ENTRADA', 'AJUSTE_ENTRADA'], true) && $costoUnitario < 0) {
                    throw ValidationException::withMessages([
                        'detalles' => 'El costo unitario no puede ser negativo.',
                    ]);
                }

                $costoDetalle = $this->costoDetalle($data, $producto, $costoUnitario);

                $movimiento->detalles()->create([
                    'producto_id' => $producto->id,
                    'cantidad' => $cantidad,
                    'costo_unitario' => $costoDetalle,
                    'costo_total' => round($cantidad * $costoDetalle, 4),
                    'lote' => $detalle['lote'] ?? null,
                    'vencimiento' => $detalle['vencimiento'] ?? null,
                ]);

                match ($data['tipo']) {
                    'ENTRADA', 'AJUSTE_ENTRADA' => $this->aplicarEntrada(
                        movimiento: $movimiento,
                        producto: $producto,
                        almacenId: (int) $data['almacen_destino_id'],
                        cantidad: $cantidad,
                        costoUnitario: $costoUnitario,
                    ),
                    'SALIDA', 'AJUSTE_SALIDA' => $this->aplicarSalida(
                        movimiento: $movimiento,
                        producto: $producto,
                        almacenId: (int) $data['almacen_origen_id'],
                        cantidad: $cantidad,
                    ),
                    'TRANSFERENCIA' => $this->aplicarTransferencia(
                        movimiento: $movimiento,
                        producto: $producto,
                        almacenOrigenId: (int) $data['almacen_origen_id'],
                        almacenDestinoId: (int) $data['almacen_destino_id'],
                        cantidad: $cantidad,
                    ),
                    default => throw ValidationException::withMessages([
                        'tipo' => 'Tipo de movimiento no soportado.',
                    ]),
                };
            }

            return $movimiento->load(['detalles.producto', 'almacenOrigen', 'almacenDestino']);
        });
    }

    public function anularMovimiento(Movimiento $movimiento, User $usuario, string $motivo): Movimiento
    {
        return DB::transaction(function () use ($movimiento, $usuario, $motivo) {
            $movimiento->loadMissing(['detalles.producto']);

            if ($movimiento->estado === 'anulado') {
                throw ValidationException::withMessages([
                    'motivo' => 'El movimiento ya fue anulado.',
                ]);
            }

            foreach ($movimiento->detalles as $detalle) {
                $producto = $detalle->producto;
                $cantidad = round((float) $detalle->cantidad, 4);
                $costoUnitario = round((float) $detalle->costo_unitario, 4);

                match ($movimiento->tipo) {
                    'ENTRADA', 'AJUSTE_ENTRADA' => $this->revertirEntrada(
                        movimiento: $movimiento,
                        producto: $producto,
                        almacenId: (int) $movimiento->almacen_destino_id,
                        cantidad: $cantidad,
                        costoUnitario: $costoUnitario,
                    ),
                    'SALIDA', 'AJUSTE_SALIDA' => $this->revertirSalida(
                        movimiento: $movimiento,
                        producto: $producto,
                        almacenId: (int) $movimiento->almacen_origen_id,
                        cantidad: $cantidad,
                        costoUnitario: $costoUnitario,
                    ),
                    'TRANSFERENCIA' => $this->revertirTransferencia(
                        movimiento: $movimiento,
                        producto: $producto,
                        almacenOrigenId: (int) $movimiento->almacen_origen_id,
                        almacenDestinoId: (int) $movimiento->almacen_destino_id,
                        cantidad: $cantidad,
                        costoUnitario: $costoUnitario,
                    ),
                    default => throw ValidationException::withMessages([
                        'motivo' => 'Tipo de movimiento no soportado para anular.',
                    ]),
                };
            }

            $movimiento->update([
                'estado' => 'anulado',
                'anulado_en' => now(),
                'anulado_por' => $usuario->id,
                'motivo_anulacion' => $motivo,
            ]);

            return $movimiento->refresh();
        });
    }

    private function aplicarEntrada(
        Movimiento $movimiento,
        Producto $producto,
        int $almacenId,
        float $cantidad,
        float $costoUnitario,
    ): void {
        $stock = $this->stock($producto, $almacenId);
        $stockAnterior = (float) $stock->stock_actual;
        $costoAnterior = (float) $stock->costo_promedio;
        $totalAnterior = round($stockAnterior * $costoAnterior, 4);
        $totalEntrada = round($cantidad * $costoUnitario, 4);
        $nuevoStock = round($stockAnterior + $cantidad, 4);
        $nuevoCosto = $nuevoStock > 0 ? round(($totalAnterior + $totalEntrada) / $nuevoStock, 4) : 0;

        $stock->update([
            'stock_actual' => $nuevoStock,
            'costo_promedio' => $nuevoCosto,
        ]);

        $this->registrarKardex($movimiento, $producto, $almacenId, [
            'cantidad_entrada' => $cantidad,
            'costo_entrada' => $costoUnitario,
            'total_entrada' => $totalEntrada,
            'saldo_cantidad' => $nuevoStock,
            'saldo_costo_promedio' => $nuevoCosto,
            'saldo_total' => round($nuevoStock * $nuevoCosto, 4),
        ]);
    }

    private function aplicarSalida(Movimiento $movimiento, Producto $producto, int $almacenId, float $cantidad): void
    {
        $stock = $this->stock($producto, $almacenId);
        $stockAnterior = (float) $stock->stock_actual;
        $costoPromedio = (float) $stock->costo_promedio;

        if ($stockAnterior < $cantidad) {
            throw ValidationException::withMessages([
                'detalles' => "Stock insuficiente para {$producto->codigo}. Disponible: {$stockAnterior}.",
            ]);
        }

        $nuevoStock = round($stockAnterior - $cantidad, 4);
        $totalSalida = round($cantidad * $costoPromedio, 4);

        $stock->update([
            'stock_actual' => $nuevoStock,
        ]);

        $this->registrarKardex($movimiento, $producto, $almacenId, [
            'cantidad_salida' => $cantidad,
            'costo_salida' => $costoPromedio,
            'total_salida' => $totalSalida,
            'saldo_cantidad' => $nuevoStock,
            'saldo_costo_promedio' => $costoPromedio,
            'saldo_total' => round($nuevoStock * $costoPromedio, 4),
        ]);
    }

    private function aplicarTransferencia(
        Movimiento $movimiento,
        Producto $producto,
        int $almacenOrigenId,
        int $almacenDestinoId,
        float $cantidad,
    ): void {
        if ($almacenOrigenId === $almacenDestinoId) {
            throw ValidationException::withMessages([
                'almacen_destino_id' => 'El almacen destino debe ser distinto al origen.',
            ]);
        }

        $origen = $this->stock($producto, $almacenOrigenId);
        $costoOrigen = (float) $origen->costo_promedio;

        $this->aplicarSalida($movimiento, $producto, $almacenOrigenId, $cantidad);
        $this->aplicarEntrada($movimiento, $producto, $almacenDestinoId, $cantidad, $costoOrigen);
    }

    private function revertirEntrada(
        Movimiento $movimiento,
        Producto $producto,
        int $almacenId,
        float $cantidad,
        float $costoUnitario,
    ): void {
        $stock = $this->stock($producto, $almacenId);
        $stockAnterior = (float) $stock->stock_actual;

        if ($stockAnterior < $cantidad) {
            throw ValidationException::withMessages([
                'motivo' => "No se puede anular {$movimiento->numero}: stock insuficiente para revertir {$producto->codigo}. Disponible: {$stockAnterior}.",
            ]);
        }

        $costoAnterior = (float) $stock->costo_promedio;
        $totalAnterior = round($stockAnterior * $costoAnterior, 4);
        $totalReverso = round($cantidad * $costoUnitario, 4);
        $nuevoStock = round($stockAnterior - $cantidad, 4);
        $nuevoCosto = $nuevoStock > 0 ? round(max($totalAnterior - $totalReverso, 0) / $nuevoStock, 4) : 0;

        $stock->update([
            'stock_actual' => $nuevoStock,
            'costo_promedio' => $nuevoCosto,
        ]);

        $this->registrarKardex($movimiento, $producto, $almacenId, [
            'tipo' => 'ANULACION_ENTRADA',
            'cantidad_salida' => $cantidad,
            'costo_salida' => $costoUnitario,
            'total_salida' => $totalReverso,
            'saldo_cantidad' => $nuevoStock,
            'saldo_costo_promedio' => $nuevoCosto,
            'saldo_total' => round($nuevoStock * $nuevoCosto, 4),
        ]);
    }

    private function revertirSalida(
        Movimiento $movimiento,
        Producto $producto,
        int $almacenId,
        float $cantidad,
        float $costoUnitario,
    ): void {
        $stock = $this->stock($producto, $almacenId);
        $stockAnterior = (float) $stock->stock_actual;
        $costoAnterior = (float) $stock->costo_promedio;
        $totalAnterior = round($stockAnterior * $costoAnterior, 4);
        $totalReverso = round($cantidad * $costoUnitario, 4);
        $nuevoStock = round($stockAnterior + $cantidad, 4);
        $nuevoCosto = $nuevoStock > 0 ? round(($totalAnterior + $totalReverso) / $nuevoStock, 4) : 0;

        $stock->update([
            'stock_actual' => $nuevoStock,
            'costo_promedio' => $nuevoCosto,
        ]);

        $this->registrarKardex($movimiento, $producto, $almacenId, [
            'tipo' => 'ANULACION_SALIDA',
            'cantidad_entrada' => $cantidad,
            'costo_entrada' => $costoUnitario,
            'total_entrada' => $totalReverso,
            'saldo_cantidad' => $nuevoStock,
            'saldo_costo_promedio' => $nuevoCosto,
            'saldo_total' => round($nuevoStock * $nuevoCosto, 4),
        ]);
    }

    private function revertirTransferencia(
        Movimiento $movimiento,
        Producto $producto,
        int $almacenOrigenId,
        int $almacenDestinoId,
        float $cantidad,
        float $costoUnitario,
    ): void {
        $this->revertirEntrada($movimiento, $producto, $almacenDestinoId, $cantidad, $costoUnitario);
        $this->revertirSalida($movimiento, $producto, $almacenOrigenId, $cantidad, $costoUnitario);
    }

    private function stock(Producto $producto, int $almacenId): StockAlmacen
    {
        $stock = StockAlmacen::query()
            ->where('producto_id', $producto->id)
            ->where('almacen_id', $almacenId)
            ->lockForUpdate()
            ->first();

        if ($stock) {
            return $stock;
        }

        try {
            StockAlmacen::query()->create([
                'producto_id' => $producto->id,
                'almacen_id' => $almacenId,
                'stock_actual' => 0,
                'stock_minimo' => $producto->stock_minimo ?? 0,
                'stock_maximo' => $producto->stock_maximo,
                'costo_promedio' => 0,
            ]);
        } catch (QueryException $exception) {
            if ((string) $exception->getCode() !== '23000') {
                throw $exception;
            }
        }

        return StockAlmacen::query()
            ->where('producto_id', $producto->id)
            ->where('almacen_id', $almacenId)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function costoDetalle(array $data, Producto $producto, float $costoUnitario): float
    {
        if (in_array($data['tipo'], ['ENTRADA', 'AJUSTE_ENTRADA'], true)) {
            return $costoUnitario;
        }

        if (in_array($data['tipo'], ['SALIDA', 'AJUSTE_SALIDA', 'TRANSFERENCIA'], true)) {
            $almacenId = (int) $data['almacen_origen_id'];

            return (float) $this->stock($producto, $almacenId)->costo_promedio;
        }

        return $costoUnitario;
    }

    private function registrarKardex(Movimiento $movimiento, Producto $producto, int $almacenId, array $values): void
    {
        Kardex::query()->create(array_merge([
            'producto_id' => $producto->id,
            'almacen_id' => $almacenId,
            'movimiento_id' => $movimiento->id,
            'fecha' => $movimiento->fecha,
            'tipo' => $movimiento->tipo,
            'cantidad_entrada' => 0,
            'costo_entrada' => 0,
            'total_entrada' => 0,
            'cantidad_salida' => 0,
            'costo_salida' => 0,
            'total_salida' => 0,
        ], $values));
    }

    private function generarNumero(string $tipo): string
    {
        $prefix = match ($tipo) {
            'ENTRADA' => 'ENT',
            'SALIDA' => 'SAL',
            'TRANSFERENCIA' => 'TRA',
            'AJUSTE_ENTRADA', 'AJUSTE_SALIDA' => 'AJU',
            default => 'MOV',
        };

        $year = now()->format('Y');
        $count = Movimiento::query()
            ->where('numero', 'like', "{$prefix}-{$year}-%")
            ->lockForUpdate()
            ->count() + 1;

        return sprintf('%s-%s-%06d', $prefix, $year, $count);
    }
}
