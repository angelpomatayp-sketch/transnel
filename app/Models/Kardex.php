<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Kardex extends Model
{
    protected $table = 'kardex';

    protected $fillable = [
        'producto_id',
        'almacen_id',
        'movimiento_id',
        'fecha',
        'tipo',
        'cantidad_entrada',
        'costo_entrada',
        'total_entrada',
        'cantidad_salida',
        'costo_salida',
        'total_salida',
        'saldo_cantidad',
        'saldo_costo_promedio',
        'saldo_total',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'cantidad_entrada' => 'decimal:4',
            'costo_entrada' => 'decimal:4',
            'total_entrada' => 'decimal:4',
            'cantidad_salida' => 'decimal:4',
            'costo_salida' => 'decimal:4',
            'total_salida' => 'decimal:4',
            'saldo_cantidad' => 'decimal:4',
            'saldo_costo_promedio' => 'decimal:4',
            'saldo_total' => 'decimal:4',
        ];
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class);
    }

    public function movimiento(): BelongsTo
    {
        return $this->belongsTo(Movimiento::class);
    }
}
