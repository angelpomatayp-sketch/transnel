<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoDetalle extends Model
{
    protected $table = 'movimientos_detalle';

    protected $fillable = [
        'movimiento_id',
        'producto_id',
        'cantidad',
        'costo_unitario',
        'costo_total',
        'lote',
        'vencimiento',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'decimal:4',
            'costo_unitario' => 'decimal:4',
            'costo_total' => 'decimal:4',
            'vencimiento' => 'date',
        ];
    }

    public function movimiento(): BelongsTo
    {
        return $this->belongsTo(Movimiento::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
