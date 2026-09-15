<?php

namespace App\Models\Concerns;

trait Blameable
{
    protected static function bootBlameable(): void
    {
        static::creating(function ($model): void {
            if (blank($model->created_by) && auth()->check()) {
                $model->created_by = auth()->id();
            }

            if (blank($model->updated_by) && auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });

        static::updating(function ($model): void {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });
    }
}
