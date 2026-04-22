<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use Illuminate\Console\Command;

class DataPurgeCommand extends Command
{
    protected $signature = 'data:purge';
    protected $description = 'Purge request logs and anonymize stale audit entries for LGPD retention policy';

    public function handle(): int
    {
        $requestLogRetentionDays = (int) config('data_retention.request_log_retention_days', 90);
        $auditAnonymizeDays = (int) config('data_retention.audit_anonymize_days', 180);
        $auditRetentionDays = (int) config('data_retention.audit_retention_days', 365);

        $requestLogCutoff = now()->subDays($requestLogRetentionDays);
        $anonymizeCutoff = now()->subDays($auditAnonymizeDays);
        $auditDeleteCutoff = now()->subDays($auditRetentionDays);

        $deletedRequestLogs = AuditLog::query()
            ->where('created_at', '<', $requestLogCutoff)
            ->whereRaw("JSON_EXTRACT(tags, '$.request_log') = true")
            ->delete();

        $anonymized = 0;

        AuditLog::query()
            ->where('created_at', '<', $anonymizeCutoff)
            ->where(function ($query) {
                $query->whereNotNull('old_values')->orWhereNotNull('new_values');
            })
            ->orderBy('id')
            ->chunkById(200, function ($logs) use (&$anonymized) {
                foreach ($logs as $log) {
                    $oldValues = $this->anonymizeAuditData($log->old_values);
                    $newValues = $this->anonymizeAuditData($log->new_values);

                    $log->old_values = $oldValues;
                    $log->new_values = $newValues;
                    $log->ip_address = null;
                    $log->user_agent = null;
                    $log->save();
                    $anonymized++;
                }
            });

        $deletedOldAudits = AuditLog::query()
            ->where('created_at', '<', $auditDeleteCutoff)
            ->delete();

        // Cleanup abandoned Treasury Drafts (older than 24h)
        $deletedDrafts = \App\Models\TreasuryEntry::where('status', 'draft')
            ->where('updated_at', '<', now()->subDay())
            ->delete();

        $this->info("Request logs removidos: {$deletedRequestLogs}");
        $this->info("Auditorias anonimizadas: {$anonymized}");
        $this->info("Auditorias antigas removidas: {$deletedOldAudits}");
        $this->info("Rascunhos de diaconia (Bordereaus) abandonados removidos: {$deletedDrafts}");

        return self::SUCCESS;
    }

    private function anonymizeAuditData($data)
    {
        if (!$data) {
            return $data;
        }

        $sensitiveKeys = ['cpf', 'email', 'phone', 'telefone', 'celular'];

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (is_string($key) && in_array(strtolower($key), $sensitiveKeys, true)) {
                    $data[$key] = '[ANONYMIZED]';
                    continue;
                }

                if (is_array($value)) {
                    $data[$key] = $this->anonymizeAuditData($value);
                    continue;
                }

                if (is_string($value)) {
                    $masked = $value;
                    $masked = preg_replace('/\b\d{11}\b/', '[ANONYMIZED_CPF]', $masked);
                    $masked = preg_replace('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', '[ANONYMIZED_EMAIL]', $masked);
                    $masked = preg_replace('/\b(?:\+?55\s?)?(?:\(?\d{2}\)?\s?)?\d{4,5}-?\d{4}\b/', '[ANONYMIZED_PHONE]', $masked);
                    $data[$key] = $masked;
                }
            }
        }

        return $data;
    }
}
