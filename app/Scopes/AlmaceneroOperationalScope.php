<?php

namespace App\Scopes;

use App\Services\AccessControlService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class AlmaceneroOperationalScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (app()->runningInConsole()) {
            return;
        }

        app(AccessControlService::class)->applyOperationalScope($builder, $model->getTable());
    }
}
