<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrestamoDetalle extends Model
{
    protected $table = 'prestamo_detalles';

    protected $fillable = [
        'prestamo_id',
        'producto_id',
        'cantidad',
        'cantidad_devuelta',
        'observaciones',
    ];

    protected $casts = [
        'cantidad' => 'decimal:4',
        'cantidad_devuelta' => 'decimal:4',
    ];

    public function prestamo(): BelongsTo
    {
        return $this->belongsTo(Prestamo::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
