<?php

namespace App\Observers;

use App\Contracts\LoggerInterface;
use App\Models\BackgroundJobLog;

/**
 * Observer for BackgroundJobLog model.
 *
 * Logs updates to job status for monitoring and debugging.
 *
 * @package App\Observers
 */
class JobLogObserver
{
    /**
     * The logger instance.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Create a new observer instance.
     *
     * @param LoggerInterface $logger The logger service.
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Handle the "updated" event for the BackgroundJobLog model.
     *
     * Logs the job status update with relevant details.
     *
     * @param BackgroundJobLog $job The job log instance.
     * @return void
     */
    public function updated(BackgroundJobLog $job): void
    {
        $this->logger->info("Job status updated: {$job->class}::{$job->method}", [
            'status' => $job->status,
            'job_id' => $job->id,
        ]);
    }
}
