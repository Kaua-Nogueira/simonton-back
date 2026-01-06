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
        
        // You can add restored() or forceDeleted() if using SoftDeletes
    }

    /**
     * Create an audit log entry.
     */
    protected static function audit(string $action, Model $model): void
    {
        $oldValues = null;
        $newValues = null;

        if ($action === 'create') {
            $newValues = $model->getAttributes();
        } elseif ($action === 'update') {
            $oldValues = $model->getOriginal(); // or just specific changed keys
            $newValues = $model->getChanges();
        } elseif ($action === 'delete') {
            $oldValues = $model->getAttributes();
        }

        AuditLog::create([
            'user_id'        => Auth::id(), // Will be null if strictly system action, or if not logged in
            'auditable_type' => get_class($model),
            'auditable_id'   => $model->getKey(),
            'action'         => $action,
            'old_values'     => $oldValues,
            'new_values'     => $newValues,
            'url'            => request()->fullUrl(),
            'ip_address'     => request()->ip(),
            'user_agent'     => request()->userAgent(),
            'tags'           => null, // Can be extended later to support custom context
        ]);
    }
}
