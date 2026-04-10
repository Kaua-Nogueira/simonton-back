<?php

return [
    // Request/system logs are purged after this period.
    'request_log_retention_days' => (int) env('REQUEST_LOG_RETENTION_DAYS', 90),

    // Audit entries older than this period are anonymized.
    'audit_anonymize_days' => (int) env('AUDIT_ANONYMIZE_DAYS', 180),

    // Hard-delete anonymized audit entries older than this period.
    'audit_retention_days' => (int) env('AUDIT_RETENTION_DAYS', 365),
];
