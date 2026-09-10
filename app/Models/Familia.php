<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Familia extends Model
{
    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'activo',
        'es_epp',
        'categoria_epp',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'es_epp' => 'boolean',
        ];
    }

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class);
    }
}
