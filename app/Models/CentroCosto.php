<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CentroCosto extends Model
{
    use SoftDeletes;

    protected $table = 'centros_costos';

    protected $fillable = [
        'codigo',
        'nombre',
        'tipo',
        'responsable_id',
        'ubicacion',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }
}
