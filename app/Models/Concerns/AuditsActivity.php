<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait AuditsActivity
{
    protected static function bootAuditsActivity(): void
    {
        static::created(function ($model): void {
            $model->recordAudit('created');
        });

        static::updated(function ($model): void {
            $model->recordAudit('updated');
        });

        static::deleted(function ($model): void {
            $model->recordAudit('deleted');
        });
    }

    public function audits(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    /**
     * Record a manual or automatic audit event against this model.
     *
     * @param  array<string, mixed>|null  $changes
     */
    public function recordAudit(string $action, ?array $changes = null): void
    {
        $changes ??= $this->getChanges();

        AuditLog::create([
            'user_id' => auth()->id(),
            'auditable_type' => $this->getMorphClass(),
            'auditable_id' => $this->getKey(),
            'action' => $action,
            'changes' => $changes === [] ? null : $changes,
            'ip_address' => request()->ip(),
        ]);
    }
}
