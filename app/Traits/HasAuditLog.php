<?php

namespace App\Traits;

use App\Models\AuditLog;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Records created / updated / deleted / restored for the model in `audit_logs`.
 *
 * Event names are `{model_snake}.{action}` (user.created). Attributes in
 * the model's `$auditExcept` are never stored; hidden attributes are stored masked, so a
 * password change shows up without leaking the value. Model must be in the
 * morph map (AppServiceProvider).
 *
 * @mixin Model
 */
trait HasAuditLog
{
    private const REDACTED = '[redacted]';

    public static function bootHasAuditLog(): void
    {
        static::created(fn (self $model) => $model->recordAudit('created', [], $model->getAttributes()));

        static::updated(fn (self $model) => $model->recordAudit(
            'updated',
            Arr::map($model->getChanges(), fn ($value, $key) => $model->getRawOriginal($key)),
            $model->getChanges(),
        ));

        static::deleted(fn (self $model) => $model->recordAudit(
            'deleted',
            $model->getAttributes(),
            [],
            ['force' => property_exists($model, 'forceDeleting') && $model->forceDeleting],
        ));

        static::registerModelEvent('restored', fn (self $model) => $model->recordAudit('restored'));
    }

    /** @return MorphMany<AuditLog, $this> */
    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    /**
     * Attributes that must never reach the audit log: the primary key, the bookkeeping columns plus
     * whatever the model lists in an optional `protected array $auditExcept`.
     *
     * @return list<string>
     */
    private function auditExclude(): array
    {
        /** @var list<string> $extra */
        $extra = property_exists($this, 'auditExcept') ? $this->auditExcept : [];

        return [$this->getKeyName(), 'created_at', 'updated_at', 'deleted_at', 'created_by', 'updated_by', 'deleted_by', ...$extra];
    }

    /**
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     * @param  array<string, mixed>  $meta
     */
    protected function recordAudit(string $action, array $old = [], array $new = [], array $meta = []): void
    {
        $old = $this->sanitizeForAudit($old);
        $new = $this->sanitizeForAudit($new);

        if ($action === 'updated' && $new === []) {
            return;
        }

        app(AuditLogger::class)->record(
            Str::snake(class_basename($this)).'.'.$action,
            $this,
            $old,
            $new,
            $meta,
        );
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function sanitizeForAudit(array $values): array
    {
        $values = Arr::except($values, $this->auditExclude());

        foreach ($this->getHidden() as $hidden) {
            if (array_key_exists($hidden, $values)) {
                $values[$hidden] = self::REDACTED;
            }
        }

        return $values;
    }
}
