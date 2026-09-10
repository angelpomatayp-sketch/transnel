<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmpresaConfiguracion extends Model
{
    protected $table = 'empresa_configuracion';

    protected $fillable = [
        'razon_social',
        'nombre_comercial',
        'ruc',
        'direccion',
        'ciudad',
        'pais',
        'telefono',
        'email',
        'rubro',
        'moneda',
        'metodo_valorizacion',
        'bloquear_stock_negativo',
    ];

    protected function casts(): array
    {
        return [
            'bloquear_stock_negativo' => 'boolean',
        ];
    }
}
