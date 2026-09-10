<?php

namespace App\Services;

use App\Models\Requerimiento;
use App\Models\ValeSalida;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RequerimientoService
{
    public function crearRequerimiento(array $data): Requerimiento
    {
        return DB::transaction(function () use ($data) {
            $requerimiento = Requerimiento::query()->create([
                'numero' => $this->numero('REQ'),
                'solicitante_id' => $data['solicitante_id'],
                'almacen_id' => $data['almacen_id'],
                'centro_costo_id' => $data['centro_costo_id'] ?? null,
                'fecha' => $data['fecha'],
                'prioridad' => $data['prioridad'],
                'estado' => 'pendiente',
                'motivo' => $data['motivo'] ?? null,
            ]);

            foreach ($data['detalles'] as $detalle) {
                $requerimiento->detalles()->create([
                    'producto_id' => $detalle['producto_id'],
                    'cantidad_solicitada' => $detalle['cantidad_solicitada'],
                    'cantidad_aprobada' => 0,
                    'cantidad_entregada' => 0,
                    'especificaciones' => $detalle['especificaciones'] ?? null,
                ]);
            }

            return $requerimiento->load('detalles.producto');
        });
    }

    public function aprobar(Requerimiento $requerimiento, int $usuarioId, ?string $comentario = null): Requerimiento
    {
        if ($requerimiento->estado !== 'pendiente') {
            throw ValidationException::withMessages(['estado' => 'Solo se aprueban requerimientos pendientes.']);
        }

        return DB::transaction(function () use ($requerimiento, $usuarioId, $comentario) {
            foreach ($requerimiento->detalles as $detalle) {
                $detalle->update(['cantidad_aprobada' => $detalle->cantidad_solicitada]);
            }

            $requerimiento->update([
                'estado' => 'aprobado',
                'aprobado_por' => $usuarioId,
                'aprobado_en' => now(),
                'comentario_aprobacion' => $comentario,
            ]);

            return $requerimiento->refresh();
        });
    }

    public function rechazar(Requerimiento $requerimiento, int $usuarioId, ?string $comentario = null): Requerimiento
    {
        if ($requerimiento->estado !== 'pendiente') {
            throw ValidationException::withMessages(['estado' => 'Solo se rechazan requerimientos pendientes.']);
        }

        $requerimiento->update([
            'estado' => 'rechazado',
            'aprobado_por' => $usuarioId,
            'aprobado_en' => now(),
            'comentario_aprobacion' => $comentario,
        ]);

        return $requerimiento->refresh();
    }

    public function generarVale(Requerimiento $requerimiento): ValeSalida
    {
        if ($requerimiento->estado !== 'aprobado') {
            throw ValidationException::withMessages(['estado' => 'El requerimiento debe estar aprobado.']);
        }

        return DB::transaction(function () use ($requerimiento) {
            $vale = ValeSalida::query()->create([
                'numero' => $this->numero('VAL'),
                'requerimiento_id' => $requerimiento->id,
                'almacen_id' => $requerimiento->almacen_id,
                'centro_costo_id' => $requerimiento->centro_costo_id,
                'solicitante_id' => $requerimiento->solicitante_id,
                'fecha' => now()->toDateString(),
                'estado' => 'pendiente',
                'motivo' => $requerimiento->motivo,
            ]);

            foreach ($requerimiento->detalles as $detalle) {
                $pendiente = (float) $detalle->cantidad_aprobada - (float) $detalle->cantidad_entregada;
                if ($pendiente <= 0) {
                    continue;
                }

                $vale->detalles()->create([
                    'requerimiento_detalle_id' => $detalle->id,
                    'producto_id' => $detalle->producto_id,
                    'cantidad' => $pendiente,
                ]);
            }

            $requerimiento->update(['estado' => 'parcial']);

            return $vale->load('detalles.producto');
        });
    }

    public function entregarVale(ValeSalida $vale, int $usuarioId, InventarioService $inventario): ValeSalida
    {
        if ($vale->estado !== 'pendiente') {
            throw ValidationException::withMessages(['estado' => 'Solo se entregan vales pendientes.']);
        }

        return DB::transaction(function () use ($vale, $usuarioId, $inventario) {
            $movimiento = $inventario->registrarMovimiento([
                'tipo' => 'SALIDA',
                'almacen_origen_id' => $vale->almacen_id,
                'centro_costo_id' => $vale->centro_costo_id,
                'usuario_id' => $usuarioId,
                'fecha' => now()->toDateString(),
                'documento' => $vale->numero,
                'observaciones' => 'Entrega de vale de salida.',
                'detalles' => $vale->detalles->map(fn ($detalle) => [
                    'producto_id' => $detalle->producto_id,
                    'cantidad' => $detalle->cantidad,
                ])->all(),
            ]);

            $movimiento->detalles->each(function ($movimientoDetalle) use ($vale) {
                $valeDetalle = $vale->detalles->firstWhere('producto_id', $movimientoDetalle->producto_id);
                $valeDetalle?->update([
                    'costo_unitario' => $movimientoDetalle->costo_unitario,
                    'costo_total' => $movimientoDetalle->costo_total,
                ]);
            });

            foreach ($vale->detalles as $detalle) {
                if ($detalle->requerimientoDetalle) {
                    $detalle->requerimientoDetalle->increment('cantidad_entregada', (float) $detalle->cantidad);
                }
            }

            $vale->update([
                'estado' => 'entregado',
                'despachador_id' => $usuarioId,
                'movimiento_id' => $movimiento->id,
                'entregado_en' => now(),
            ]);

            if ($vale->requerimiento) {
                $vale->requerimiento->update(['estado' => 'completado']);
            }

            return $vale->refresh()->load('movimiento', 'detalles.producto');
        });
    }

    private function numero(string $prefix): string
    {
        $year = now()->format('Y');
        $model = $prefix === 'REQ' ? Requerimiento::query() : ValeSalida::query();
        $count = $model->where('numero', 'like', "{$prefix}-{$year}-%")->lockForUpdate()->count() + 1;

        return sprintf('%s-%s-%06d', $prefix, $year, $count);
    }
}
