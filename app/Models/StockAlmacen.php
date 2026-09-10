<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAlmacen extends Model
{
    protected $table = 'stock_almacen';

    protected $fillable = [
        'producto_id',
        'almacen_id',
        'stock_actual',
        'stock_minimo',
        'stock_maximo',
        'costo_promedio',
    ];

    protected function casts(): array
    {
        return [
            'stock_actual' => 'decimal:4',
            'stock_minimo' => 'decimal:4',
            'stock_maximo' => 'decimal:4',
            'costo_promedio' => 'decimal:4',
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
}
