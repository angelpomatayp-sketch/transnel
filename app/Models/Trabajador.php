<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Trabajador extends Model
{
    use SoftDeletes;

    protected $table = 'trabajadores';

    protected $fillable = [
        'centro_costo_id',
        'dni',
        'nombre',
        'nombres',
        'apellidos',
        'cargo',
        'area',
        'telefono',
        'email',
        'fecha_ingreso',
        'activo',
        'estado',
    ];

    protected $casts = [
        'fecha_ingreso' => 'date',
        'activo' => 'boolean',
    ];

    public function valesSalida(): HasMany
    {
        return $this->hasMany(ValeSalida::class);
    }

    public function getNombreCompletoAttribute(): string
    {
        return trim($this->nombres.' '.$this->apellidos);
    }
}
