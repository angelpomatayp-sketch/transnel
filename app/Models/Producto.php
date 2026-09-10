<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Producto extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'familia_id',
        'unidad_medida_id',
        'marca',
        'modelo',
        'stock_minimo',
        'stock_maximo',
        'costo_referencial',
        'ubicacion',
        'lote',
        'activo',
        'es_epp',
        'vida_util_dias',
        'dias_alerta_vencimiento',
        'dias_alerta',
        'requiere_talla',
        'tallas_disponibles',
        'tallas',
        'imagenes',
    ];

    protected function casts(): array
    {
        return [
            'stock_minimo' => 'decimal:4',
            'stock_maximo' => 'decimal:4',
            'activo' => 'boolean',
            'es_epp' => 'boolean',
            'requiere_talla' => 'boolean',
            'tallas_disponibles' => 'array',
            'imagenes' => 'array',
        ];
    }

    public function familia(): BelongsTo
    {
        return $this->belongsTo(Familia::class);
    }

    public function unidad(): BelongsTo
    {
        return $this->belongsTo(UnidadMedida::class, 'unidad_medida_id');
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(StockAlmacen::class);
    }

    public function movimientosDetalle(): HasMany
    {
        return $this->hasMany(MovimientoDetalle::class);
    }
}
