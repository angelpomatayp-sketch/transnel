<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ValeSalida extends Model
{
    use SoftDeletes;

    protected $table = 'vales_salida';

    protected $fillable = [
        'numero',
        'requerimiento_id',
        'almacen_id',
        'centro_costo_id',
        'solicitante_id',
        'trabajador_id',
        'movimiento_id',
        'fecha',
        'receptor_nombre',
        'receptor_dni',
        'entregado_a',
        'dni_receptor',
        'destino',
        'motivo',
        'observaciones',
        'estado',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    public function detalles(): HasMany
    {
        return $this->hasMany(ValeSalidaDetalle::class);
    }

    public function requerimiento(): BelongsTo
    {
        return $this->belongsTo(Requerimiento::class);
    }

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class);
    }

    public function centroCosto(): BelongsTo
    {
        return $this->belongsTo(CentroCosto::class);
    }

    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitante_id');
    }

    public function trabajador(): BelongsTo
    {
        return $this->belongsTo(Trabajador::class);
    }

    public function movimiento(): BelongsTo
    {
        return $this->belongsTo(Movimiento::class);
    }
}
