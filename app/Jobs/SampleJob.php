<?php

namespace App\Jobs;

use App\Contracts\JobInterface;
use Illuminate\Support\Facades\Log;

/**
 * Sample job implementation for testing the background job system.
 *
 * Demonstrates job execution, parameter handling, and job chaining.
 *
 * @package App\Jobs
 */
class SampleJob implements JobInterface
{
    /**
     * Handle the job execution with given parameters.
     *
     * Simulates work by sleeping for 2 seconds.
     *
     * @param array $params Parameters for the job.
     * @return bool Always returns true for this sample.
     */
    public function handle(array $params): mixed
    {
        sleep(2);
        return true;
    }

    /**
     * Process job with two string parameters.
     *
     * @param string $param1 First parameter.
     * @param string $param2 Second parameter.
     * @return bool Result of the job execution.
     */
    public function process(string $param1, string $param2): bool
    {
        return $this->handle([$param1, $param2]);
    }

    /**
     * Send an email (simulated).
     *
     * @param string $email Email address.
     * @return bool Result of the job execution.
     */
    public function sendEmail(string $email): bool
    {
        return $this->handle([$email]);
    }

    /**
     * Get the next job to be executed in a chain.
     *
     * @return array|null Array with class, method, and params, or null if no next job.
     */
    public function getNextJob(): ?array
    {
        Log::debug('SampleJob::getNextJob called', ['caller' => debug_backtrace()[1]['function']]);
        if (debug_backtrace()[1]['function'] === 'process') {
            return [
                'class' => self::class,
                'method' => 'sendEmail',
                'params' => ['test@example.com'],
            ];
        }
        return null; // Prevent sendEmail from chaining itself
    }
}
