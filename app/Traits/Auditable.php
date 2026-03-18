<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait Auditable
{
    /**
     * Boot the trait.
     */
    public static function bootAuditable(): void
    {
        static::created(function (Model $model) {
            static::audit('create', $model);
        });

        static::updated(function (Model $model) {
            static::audit('update', $model);
        });

        static::deleted(function (Model $model) {
            static::audit('delete', $model);
        });
    }

    /**
     * Create an audit log entry.
     */
    protected static function audit(string $action, Model $model): void
    {
        // Skip auditing the AuditLog model itself to avoid recursion
        if ($model instanceof AuditLog) {
            return;
        }

        $oldValues = null;
        $newValues = null;

        // Fields to omit from logs for security/redundancy
        $ignoredFields = ['password', 'remember_token', 'two_factor_recovery_codes', 'two_factor_secret', 'updated_at', 'created_at', 'deleted_at'];

        if ($action === 'create') {
            $newValues = array_diff_key($model->getAttributes(), array_flip($ignoredFields));
        } elseif ($action === 'update') {
            $oldValues = array_intersect_key($model->getOriginal(), $model->getChanges());
            $newValues = array_diff_key($model->getChanges(), array_flip($ignoredFields));
            
            // Re-sync old values to exclude ignored ones
            $oldValues = array_diff_key($oldValues, array_flip($ignoredFields));

            if (empty($newValues)) {
                return; // No meaningful changes
            }
        } elseif ($action === 'delete') {
            $oldValues = array_diff_key($model->getOriginal(), array_flip($ignoredFields));
        }

        AuditLog::create([
            'user_id'        => Auth::id(),
            'auditable_type' => get_class($model),
            'auditable_id'   => $model->getKey(),
            'action'         => $action,
            'old_values'     => $oldValues,
            'new_values'     => $newValues,
            'url'            => request()->fullUrl(),
            'ip_address'     => request()->ip(),
            'user_agent'     => request()->userAgent(),
        ]);
    }
}
