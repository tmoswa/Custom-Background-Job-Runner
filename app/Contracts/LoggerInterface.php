<?php

namespace App\Contracts;

/**
 * Interface for logging services.
 *
 * Defines methods for logging informational and error messages with context.
 *
 * @package App\Contracts
 */
interface LoggerInterface
{
    /**
     * Log an informational message.
     *
     * @param string $message The message to log.
     * @param array $context Additional context data.
     * @return void
     */
    public function info(string $message, array $context = []): void;

    /**
     * Log an error message.
     *
     * @param string $message The error message to log.
     * @param array $context Additional context data.
     * @return void
     */
    public function error(string $message, array $context = []): void;
}
