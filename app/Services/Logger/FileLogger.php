<?php

namespace App\Services\Logger;

use App\Contracts\LoggerInterface;
use Illuminate\Support\Facades\Log;

/**
 * File-based logger implementation.
 *
 * Logs messages to configured log channels for background jobs.
 *
 * @package App\Services\Logger
 */
class FileLogger implements LoggerInterface
{
    /**
     * Log an informational message.
     *
     * @param string $message The message to log.
     * @param array $context Additional context data.
     * @return void
     */
    public function info(string $message, array $context = []): void
    {
        Log::channel('background_jobs')->info($message, $context);
    }

    /**
     * Log an error message.
     *
     * @param string $message The error message to log.
     * @param array $context Additional context data.
     * @return void
     */
    public function error(string $message, array $context = []): void
    {
        Log::channel('background_jobs_errors')->error($message, $context);
    }
}
