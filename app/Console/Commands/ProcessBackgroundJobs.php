<?php

namespace App\Console\Commands;

use App\Models\BackgroundJobLog;
use App\Services\JobProcessor;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Console command to process pending background jobs in a continuous loop.
 *
 * Polls the `background_job_logs` table for jobs with `status = 'pending'` and
 * `scheduled_at <= now()` or `null`, ordered by priority. Executes jobs using
 * the JobProcessor and logs activity to the `background_jobs` channel.
 *
 * @package App\Console\Commands
 */
class ProcessBackgroundJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jobs:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process pending background jobs in priority order';

    /**
     * Execute the console command.
     *
     * Continuously polls for pending jobs, respecting priority and scheduling.
     * Logs job processing attempts and errors to `storage/logs/background_jobs.log`
     * and `storage/logs/background_jobs_errors.log`.
     *
     * @param JobProcessor $processor The job processor service.
     * @return void
     */
    public function handle(JobProcessor $processor): void
    {
        Log::channel('background_jobs')->info('Starting background job processor');

        while (true) {
            try {
                $job = BackgroundJobLog::where('status', 'pending')
                    ->where(function ($query) {
                        $query->where('scheduled_at', '<=', now())
                            ->orWhereNull('scheduled_at');
                    })
                    ->orderBy('priority', 'desc')
                    ->first();

                if (!$job) {
                    Log::channel('background_jobs')->debug('No pending jobs found, sleeping for 5 seconds');
                    sleep(5);
                    continue;
                }

                // Decode parameters
                $params = is_string($job->parameters)
                    ? json_decode($job->parameters, true)
                    : $job->parameters;

                if (!is_array($params)) {
                    throw new InvalidArgumentException("Invalid parameters format for job {$job->class}::{$job->method}");
                }

                Log::channel('background_jobs')->info("Processing job: {$job->class}::{$job->method}", [
                    'job_id' => $job->id,
                    'parameters' => $params,
                ]);

                $processor->execute($job->class, $job->method, $params);

                // Update job status to prevent reprocessing
                $job->update(['status' => 'running', 'started_at' => now()]);
            } catch (InvalidArgumentException $e) {
                $this->handleJobError($job, $e, true); // Non-recoverable
            } catch (Exception $e) {
                $this->handleJobError($job, $e, false); // Potentially recoverable
            }
        }
    }

    /**
     * Handle job processing errors.
     *
     * Logs the error, updates the job status, and retries if applicable.
     *
     * @param BackgroundJobLog|null $job The job log instance.
     * @param Exception $exception The exception that occurred.
     * @param bool $isNonRecoverable Whether the error is non-recoverable.
     * @return void
     */
    private function handleJobError(?BackgroundJobLog $job, Exception $exception, bool $isNonRecoverable): void
    {
        $context = [
            'message' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ];

        if ($job) {
            $context['job_id'] = $job->id;
            $context['class'] = $job->class;
            $context['method'] = $job->method;
            $context['parameters'] = $job->parameters;

            if ($isNonRecoverable || ($job->attempts >= config('background-jobs.max_retries'))) {
                $job->update([
                    'status' => 'failed',
                    'error_message' => $exception->getMessage(),
                    'completed_at' => now(),
                ]);
                Log::channel('background_jobs_errors')->error("Job failed permanently: {$job->class}::{$job->method}", $context);
            } else {
                $job->increment('attempts');
                $job->update(['error_message' => $exception->getMessage()]);
                Log::channel('background_jobs_errors')->warning("Job attempt failed, retrying: {$job->class}::{$job->method}", $context);
            }
        } else {
            Log::channel('background_jobs_errors')->error('Job processing error', $context);
        }

        sleep(5);
    }
}
