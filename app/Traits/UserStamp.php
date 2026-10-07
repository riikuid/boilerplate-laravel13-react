<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

/**
 * Fills created_by / updated_by / deleted_by from the authenticated user.
 *
 * The table needs `$table->userStamps()` in its migration. Without an
 * authenticated user (console, queue) the columns are left untouched.
 *
 * @mixin Model
 */
trait UserStamp
{
    public static function bootUserStamp(): void
    {
        static::creating(function (Model $model): void {
            $id = Auth::id();

            if ($id === null) {
                return;
            }

            $model->setAttribute('created_by', $model->getAttribute('created_by') ?? $id);
            $model->setAttribute('updated_by', $model->getAttribute('updated_by') ?? $id);
        });

        static::updating(function (Model $model): void {
            $id = Auth::id();

            if ($id !== null && ! $model->isDirty('updated_by')) {
                $model->setAttribute('updated_by', $id);
            }
        });

        static::deleting(function (Model $model): void {
            $id = Auth::id();

            if ($id === null || ! in_array(SoftDeletes::class, class_uses_recursive($model), true)) {
                return;
            }

            // A soft delete only writes deleted_at, so the stamp is saved separately.
            $model->forceFill(['deleted_by' => $id])->saveQuietly();
        });

        static::registerModelEvent('restoring', function (Model $model): void {
            $model->setAttribute('deleted_by', null);
        });
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** @return BelongsTo<User, $this> */
    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
