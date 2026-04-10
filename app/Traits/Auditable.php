<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait Auditable
{
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

    protected static function audit(string $action, Model $model): void
    {
        if ($model instanceof AuditLog) {
            return;
        }

        $oldValues = null;
        $newValues = null;

        $ignoredFields = [
            'password',
            'remember_token',
            'two_factor_recovery_codes',
            'two_factor_secret',
            'updated_at',
            'created_at',
            'deleted_at',
        ];

        if ($action === 'create') {
            $newValues = array_diff_key($model->getAttributes(), array_flip($ignoredFields));
        } elseif ($action === 'update') {
            $oldValues = array_intersect_key($model->getOriginal(), $model->getChanges());
            $newValues = array_diff_key($model->getChanges(), array_flip($ignoredFields));
            $oldValues = array_diff_key($oldValues, array_flip($ignoredFields));

            if (empty($newValues)) {
                return;
            }
        } elseif ($action === 'delete') {
            $oldValues = array_diff_key($model->getOriginal(), array_flip($ignoredFields));
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'auditable_type' => get_class($model),
            'auditable_id' => $model->getKey(),
            'action' => $action,
            'old_values' => static::sanitizeAuditValues($oldValues),
            'new_values' => static::sanitizeAuditValues($newValues),
            'url' => request()->fullUrl(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    protected static function sanitizeAuditValues(?array $values): ?array
    {
        if (!$values) {
            return $values;
        }

        $sensitiveKeys = [
            'password',
            'token',
            'cpf',
            'email',
            'phone',
            'address',
            'zip_code',
            'birth_date',
            'cnpj',
            'document_number',
            'attachment_path',
        ];

        foreach ($values as $key => $value) {
            if (in_array(strtolower((string) $key), $sensitiveKeys, true)) {
                $values[$key] = '[FILTERED]';
                continue;
            }

            if (is_string($value) && mb_strlen($value) > 200) {
                $values[$key] = mb_substr($value, 0, 200).'...';
            }
        }

        return $values;
    }
}
