<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequerimientoDetalle extends Model
{
    protected $table = 'requerimientos_detalle';

    protected $fillable = ['requerimiento_id','producto_id','cantidad_solicitada','cantidad_aprobada','cantidad_entregada','especificaciones'];

    protected function casts(): array
    {
        return ['cantidad_solicitada' => 'decimal:4', 'cantidad_aprobada' => 'decimal:4', 'cantidad_entregada' => 'decimal:4'];
    }

    public function requerimiento(): BelongsTo { return $this->belongsTo(Requerimiento::class); }
    public function producto(): BelongsTo { return $this->belongsTo(Producto::class); }
}
