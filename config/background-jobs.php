<?php

/**
 * Configuration for background job processing.
 *
 * Defines allowed jobs, retry policies, and logging settings.
 */

return [
    'allowed_jobs' => [
        'App\Jobs\SampleJob' => ['process', 'sendEmail'],
        'App\Jobs\FailingJob' => ['process', 'sendEmail'],
    ],
    'max_retries' => env('JOB_MAX_RETRIES', 3),
    'retry_delay' => env('JOB_RETRY_DELAY', 10),
    'default_priority' => env('JOB_DEFAULT_PRIORITY', 1),
    'log_file' => storage_path('logs/background_jobs.log'),
    'error_log_file' => storage_path('logs/background_jobs_errors.log'),
    'exception_retries' => [
        'Illuminate\Database\QueryException' => 2,
        'Exception' => 1,
    ],
    'redis_lock_key' => 'background_job_lock',
];
