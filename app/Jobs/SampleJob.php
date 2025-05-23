<?php

namespace App\Jobs;

use App\Contracts\JobInterface;
use App\Models\BackgroundJobLog;
use Illuminate\Support\Facades\Log;
use Exception;

class SampleJob implements JobInterface
{
    protected $params;
    protected $jobLogId;

    public function __construct(array $params, ?int $jobLogId = null)
    {
        $this->params = $params;
        $this->jobLogId = $jobLogId;
    }

    public function handle(array $params): mixed
    {
        $delay = config('background-jobs.retry_delay', 10);

        $jobLog = $this->jobLogId
            ? BackgroundJobLog::findOrFail($this->jobLogId)
            : BackgroundJobLog::where('class', self::class)
                ->where('parameters', json_encode($params))
                ->latest()
                ->first();

        if ($jobLog) {
            $jobLog->update([
                'status' => 'running',
                'attempts' => $jobLog->attempts + 1,
            ]);
        }

        Log::channel('background_jobs')->info("Job status updated: " . self::class . "::handle", [
            'status' => 'running',
            'job_id' => $jobLog->id ?? 'unknown',
        ]);

        // Sleep in increments, checking for cancellation
        $steps = $delay / 2; // Check every 2 seconds
        for ($i = 0; $i < $steps; $i++) {
            if ($jobLog) {
                $jobLog->refresh();
                if ($jobLog->status === 'failed') {
                    throw new Exception('Job cancelled by user');
                }
            }
            sleep(2);
        }

        if ($jobLog) {
            $jobLog->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            Log::channel('background_jobs')->info("Job status updated: " . self::class . "::handle", [
                'status' => 'completed',
                'job_id' => $jobLog->id,
            ]);
        }

        return true;
    }

    public function process(string $param1, string $param2): bool
    {
        return $this->handle([$param1, $param2]);
    }

    public function sendEmail(string $email): bool
    {
        return $this->handle([$email]);
    }

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
        return null;
    }
}
