<?php

namespace App\Services;

use App\Contracts\JobProcessorInterface;
use App\Contracts\LoggerInterface;
use App\Jobs\Strategies\RetryStrategy;
use App\Models\BackgroundJobLog;
use Exception;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Service for processing background jobs.
 *
 * Validates and executes jobs, handles retries, and supports job chaining.
 *
 * @package App\Services
 */
class JobProcessor implements JobProcessorInterface
{
    /**
     * The logger instance.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * The retry strategy instance.
     *
     * @var RetryStrategy
     */
    protected $retryStrategy;

    /**
     * Create a new job processor instance.
     *
     * @param LoggerInterface $logger The logger service.
     * @param RetryStrategy $retryStrategy The retry strategy service.
     */
    public function __construct(LoggerInterface $logger, RetryStrategy $retryStrategy)
    {
        $this->logger = $logger;
        $this->retryStrategy = $retryStrategy;
    }

    /**
     * Execute a background job.
     *
     * Validates the job, executes it, logs the result, and handles chaining.
     *
     * @param string $class The fully qualified class name of the job.
     * @param string $method The method to call on the job class.
     * @param array $params Parameters to pass to the method.
     * @throws InvalidArgumentException If the job is invalid or not found.
     * @return void
     */

    public function execute(string $class, string $method, array $params, ?BackgroundJobLog $jobLog = null): void
    {
        $this->logger->info("Executing job: {$class}::{$method}", ['params' => $params]);

        $config = config('background-jobs');

        // Check authorization first
        if (!isset($config['allowed_jobs'][$class]) || !in_array($method, $config['allowed_jobs'][$class])) {
            $this->logger->error("Unauthorized job: {$class}::{$method}");
            throw new InvalidArgumentException("Unauthorized job: {$class}::{$method}");
        }

        // Create or retrieve job log only if authorized
        if (!$jobLog) {
            $jobLog = BackgroundJobLog::firstOrCreate(
                [
                    'class' => $class,
                    'method' => $method,
                    'parameters' => json_encode($params),
                ],
                [
                    'status' => 'pending',
                    'priority' => 1,
                    'scheduled_at' => null,
                ]
            );
        }

        try {
            if (!class_exists($class)) {
                throw new InvalidArgumentException("Class does not exist: {$class}");
            }

            if (!method_exists($class, $method)) {
                $this->logger->error("Method check failed: {$class}::{$method}");
                throw new InvalidArgumentException("Method does not exist: {$class}::{$method}");
            }

            $instance = App::make($class);
            $this->logger->info("Calling {$class}::{$method}", ['instance_class' => get_class($instance)]);
            $result = call_user_func_array([$instance, $method], $params);

            $jobLog->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            $this->logger->info("Job completed: {$class}::{$method}", ['job_id' => $jobLog->id]);

            if (method_exists($instance, 'getNextJob')) {
                $nextJob = $instance->getNextJob();
                if ($nextJob && is_array($nextJob) && isset($nextJob['class'], $nextJob['method'], $nextJob['params'])) {
                    $this->logger->info("Executing follow-up job: {$nextJob['class']}::{$nextJob['method']}");
                    $this->execute($nextJob['class'], $nextJob['method'], $nextJob['params']);
                }
            }
        } catch (Exception $e) {
            $this->logger->error("Exception in job: {$e->getMessage()}");

            $this->retryStrategy->handle($jobLog, $e, function () use ($class, $method, $params, $jobLog) {
                $this->execute($class, $method, $params, $jobLog);
            });
        }
    }


}
