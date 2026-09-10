<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrdenCompraDetalle extends Model
{
    protected $table = 'orden_compra_detalles';

    protected $fillable = [
        'orden_compra_id',
        'producto_id',
        'cantidad',
        'cantidad_recibida',
        'precio_unitario',
        'subtotal',
        'observaciones',
    ];

    protected $casts = [
        'cantidad' => 'decimal:4',
        'cantidad_recibida' => 'decimal:4',
        'precio_unitario' => 'decimal:4',
        'subtotal' => 'decimal:2',
    ];

    public function ordenCompra(): BelongsTo
    {
        return $this->belongsTo(OrdenCompra::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
