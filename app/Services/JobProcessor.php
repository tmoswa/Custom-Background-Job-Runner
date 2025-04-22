<?php

namespace App\Services;

use App\Contracts\JobProcessorInterface;
use App\Contracts\LoggerInterface;
use App\Jobs\Strategies\RetryStrategy;
use App\Models\BackgroundJobLog;
use Exception;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class JobProcessor implements JobProcessorInterface
{
    protected $logger;
    protected $retryStrategy;

    public function __construct(LoggerInterface $logger, RetryStrategy $retryStrategy)
    {
        $this->logger = $logger;
        $this->retryStrategy = $retryStrategy;
    }

    public function execute(string $class, string $method, array $params, ?BackgroundJobLog $jobLog = null): void
    {
        $this->logger->info("Executing job: {$class}::{$method}", ['params' => $params]);

        $config = config('background-jobs');

        if (!isset($config['allowed_jobs'][$class]) || !in_array($method, $config['allowed_jobs'][$class])) {
            $this->logger->error("Unauthorized job: {$class}::{$method}");
            throw new InvalidArgumentException("Unauthorized job: {$class}::{$method}");
        }

        if (!$jobLog) {
            $jobLog = BackgroundJobLog::create([
                'class' => $class,
                'method' => $method,
                'parameters' => json_encode($params),
                'status' => 'pending',
                'priority' => config('background-jobs.default_priority', 1),
                'scheduled_at' => now(),
            ]);
        }

        try {
            if (!class_exists($class)) {
                throw new InvalidArgumentException("Class does not exist: {$class}");
            }

            if (!method_exists($class, $method)) {
                $this->logger->error("Method check failed: {$class}::{$method}");
                throw new InvalidArgumentException("Method does not exist: {$class}::{$method}");
            }

            $jobLog->update(['status' => 'running']);

            $instance = new $class($params, $jobLog->id);
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
            $jobLog->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);
            $this->retryStrategy->handle($jobLog, $e, function () use ($class, $method, $params, $jobLog) {
                $this->execute($class, $method, $params, $jobLog);
            });
        }
    }

    public function processQueue(): void
    {
        while ($jobLog = BackgroundJobLog::where('status', 'pending')
            ->orderBy('priority', 'desc')
            ->orderBy('scheduled_at', 'asc')
            ->first()) {
            $params = json_decode($jobLog->parameters, true) ?? [];
            $this->execute($jobLog->class, $jobLog->method, $params, $jobLog);
        }
    }
}
