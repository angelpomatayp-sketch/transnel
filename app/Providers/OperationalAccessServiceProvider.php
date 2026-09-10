<?php

namespace App\Providers;

use App\Scopes\AlmaceneroOperationalScope;
use Illuminate\Support\ServiceProvider;

class OperationalAccessServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        foreach ($this->operationalModels() as $model) {
            if (class_exists($model)) {
                $model::addGlobalScope(new AlmaceneroOperationalScope());
            }
        }
    }

    private function operationalModels(): array
    {
        return [
            \App\Models\Movimiento::class,
            \App\Models\Stock::class,
            \App\Models\Requerimiento::class,
            \App\Models\ValeSalida::class,
            \App\Models\Prestamo::class,
            \App\Models\EppAsignacion::class,
            \App\Models\OrdenCompra::class,
        ];
    }
}
