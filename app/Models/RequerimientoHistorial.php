<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequerimientoHistorial extends Model
{
    protected $table = 'requerimiento_historial';

    protected $fillable = [
        'requerimiento_id',
        'usuario_id',
        'accion',
        'estado_anterior',
        'estado_nuevo',
        'comentario',
    ];

    public function requerimiento(): BelongsTo
    {
        return $this->belongsTo(Requerimiento::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
