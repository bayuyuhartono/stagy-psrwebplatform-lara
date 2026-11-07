<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Audit log retention
    |--------------------------------------------------------------------------
    |
    | Days an audit log entry is kept before `model:prune` removes it. The
    | scheduler runs the prune nightly (see routes/console.php).
    |
    | Set to 0 to retain entries indefinitely and disable pruning entirely.
    | Choose a value that satisfies whatever compliance obligation applies to
    | this deployment — one year is a common default, not a legal guarantee.
    |
    */
    'retention_days' => (int) env('AUDIT_RETENTION_DAYS', 365),
];
