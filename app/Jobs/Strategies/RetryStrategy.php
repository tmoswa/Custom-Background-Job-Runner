<?php

namespace App\Jobs\Strategies;

use App\Models\BackgroundJobLog;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class RetryStrategy
{
    public function handle(BackgroundJobLog $job, Exception $exception, callable $callback): void
    {
        $maxAttempts = Config::get('background-jobs.max_retries', 3);

        $job->increment('attempts');
        $job->update(['error_message' => $exception->getMessage()]);

        if ($job->attempts >= $maxAttempts) {
            $job->update([
                'status' => 'failed',
                'completed_at' => now(),
            ]);
            Log::channel('background_jobs')->error("Job {$job->id} failed after {$maxAttempts} attempts: {$exception->getMessage()}");
            return; // Explicitly return to avoid callback execution
        }

        // Simulate delay (skipped in testing)
        if (app()->environment('testing')) {
            $callback();
        } else {
            sleep($this->calculateDelay($job->attempts));
            $callback();
        }
    }

    protected function calculateDelay(int $attempts): int
    {
        // Exponential backoff: 1s, 2s, 4s, etc.
        return (2 ** $attempts);
    }
}
