<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Prestamo extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'codigo',
        'trabajador_id',
        'almacen_id',
        'centro_costo_id',
        'movimiento_salida_id',
        'movimiento_entrada_id',
        'usuario_id',
        'fecha_prestamo',
        'fecha_devolucion_programada',
        'fecha_devolucion',
        'estado',
        'observaciones',
        'motivo_devolucion',
    ];

    protected $casts = [
        'fecha_prestamo' => 'date',
        'fecha_devolucion_programada' => 'date',
        'fecha_devolucion' => 'datetime',
    ];

    public function detalles(): HasMany
    {
        return $this->hasMany(PrestamoDetalle::class);
    }

    public function trabajador(): BelongsTo
    {
        return $this->belongsTo(Trabajador::class);
    }

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class);
    }

    public function centroCosto(): BelongsTo
    {
        return $this->belongsTo(CentroCosto::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
