<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ValeSalidaDetalle extends Model
{
    protected $table = 'vale_salida_detalles';

    protected $fillable = [
        'vale_salida_id',
        'producto_id',
        'cantidad',
        'observaciones',
    ];

    protected $casts = [
        'cantidad' => 'decimal:4',
    ];

    public function valeSalida(): BelongsTo
    {
        return $this->belongsTo(ValeSalida::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
