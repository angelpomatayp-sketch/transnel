<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Requerimiento extends Model
{
    use SoftDeletes;

    protected $fillable = ['numero','solicitante_id','almacen_id','centro_costo_id','fecha','prioridad','estado','motivo','aprobado_por','aprobado_en','comentario_aprobacion'];

    protected function casts(): array
    {
        return ['fecha' => 'date', 'aprobado_en' => 'datetime'];
    }

    public function detalles(): HasMany { return $this->hasMany(RequerimientoDetalle::class); }
    public function vales(): HasMany { return $this->hasMany(ValeSalida::class); }
    public function solicitante(): BelongsTo { return $this->belongsTo(User::class, 'solicitante_id'); }
    public function almacen(): BelongsTo { return $this->belongsTo(Almacen::class); }
    public function centroCosto(): BelongsTo { return $this->belongsTo(CentroCosto::class); }
}
