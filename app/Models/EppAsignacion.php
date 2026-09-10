<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EppAsignacion extends Model
{
    use SoftDeletes;

    protected $table = 'epp_asignaciones';

    protected $fillable = [
        'codigo',
        'trabajador_id',
        'producto_id',
        'almacen_id',
        'centro_costo_id',
        'movimiento_id',
        'cantidad',
        'talla',
        'fecha_entrega',
        'fecha_vencimiento',
        'estado',
        'observaciones',
        'fecha_devolucion',
        'motivo_devolucion',
    ];

    protected $casts = [
        'cantidad' => 'decimal:4',
        'fecha_entrega' => 'date',
        'fecha_vencimiento' => 'date',
        'fecha_devolucion' => 'datetime',
    ];

    public function trabajador(): BelongsTo
    {
        return $this->belongsTo(Trabajador::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class);
    }

    public function centroCosto(): BelongsTo
    {
        return $this->belongsTo(CentroCosto::class);
    }

    public function movimiento(): BelongsTo
    {
        return $this->belongsTo(Movimiento::class);
    }
}
