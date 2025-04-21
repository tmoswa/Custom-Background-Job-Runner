<?php

use App\Models\BackgroundJobLog;
use App\Services\Logger\FileLogger;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\Process\Process;

/**
 * Helper function to run a background job.
 *
 * Validates and schedules a job for execution, either immediately or with a delay.
 * In testing mode, simulates job execution without Redis or process spawning.
 *
 * @param string $class The fully qualified class name of the job.
 * @param string $method The method to call on the job class.
 * @param array $params Parameters to pass to the method.
 * @param int $delay Delay in seconds before execution (default: 0).
 * @param int $priority Job priority (default: 1).
 * @return bool True if the job was successfully scheduled, false otherwise.
 */
if (!function_exists('runBackgroundJob')) {
    function runBackgroundJob(
        string $class,
        string $method,
        array $params = [],
        int $delay = 0,
        int $priority = 1
    ): bool {
        $config = config('background-jobs');
        $logger = app(FileLogger::class);

        // Validate allowed jobs
        if (!isset($config['allowed_jobs'][$class]) || !in_array($method, $config['allowed_jobs'][$class])) {
            $logger->error("Unauthorized job: {$class}::{$method}");
            return false;
        }

        // Sanitize parameters
        $params = array_map('strval', $params);

        // Create job log
        $jobLog = BackgroundJobLog::create([
            'class' => $class,
            'method' => $method,
            'parameters' => $params,
            'status' => 'pending',
            'priority' => $priority,
            'scheduled_at' => $delay > 0 ? now()->addSeconds($delay) : null,
        ]);

        // Testing environment: simulate execution
        if (app()->environment('testing')) {
            $logger->info("Job scheduled (test mode): {$class}::{$method}", [
                'parameters' => $params,
                'job_id' => $jobLog->id,
            ]);
            return true;
        }

        // Production environment: use Redis lock and process execution
        $lock = Redis::setnx($config['redis_lock_key'], 1);
        if (!$lock) {
            $logger->error("Failed to acquire lock for job: {$class}::{$method}");
            return false;
        }

        try {
            // Escape class name for command line
            $escapedClass = str_replace('\\', '\\\\', $class);
            $command = ['php', base_path('public/run-job.php'), $escapedClass, $method, implode(',', $params)];
            $process = app()->make(Process::class, ['command' => $command]); // Use make for clarity

            // Debug: Verify Process instance and isRunning
            dump('Process instance in helper:', $process);
            $process->setWorkingDirectory(base_path());

            if ($delay > 0) {
                sleep($delay); // Simplified; use a scheduler in production
            }

            $process->start();
            dump('isRunning after start:', $process->isRunning());

            if ($process->isRunning()) {
                $jobLog->update(['status' => 'running', 'started_at' => now()]);
                $logger->info("Job started: {$class}::{$method}", [
                    'parameters' => $params,
                    'job_id' => $jobLog->id,
                ]);
                return true;
            }

            $jobLog->update(['status' => 'failed', 'error_message' => 'Process failed to start']);
            return false;
        } catch (Exception $e) {
            $jobLog->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            $logger->error("Job execution failed: {$class}::{$method}", [
                'error' => $e->getMessage(),
                'job_id' => $jobLog->id,
            ]);
            return false;
        } finally {
            Redis::del($config['redis_lock_key']);
        }
    }
}
