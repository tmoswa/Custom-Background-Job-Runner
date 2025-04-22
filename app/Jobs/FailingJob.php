<?php

namespace App\Jobs;

use App\Contracts\JobInterface;
use App\Models\BackgroundJobLog;
use Illuminate\Support\Facades\Log;
use Exception;

class FailingJob implements JobInterface
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
        $delay = 2; // Total delay for FailingJob
        $steps = $delay / 2;
        for ($i = 0; $i < $steps; $i++) {
            if ($jobLog) {
                $jobLog->refresh();
                if ($jobLog->status === 'failed') {
                    throw new Exception('Job cancelled by user');
                }
            }
            sleep(2);
        }

        throw new Exception('Simulated job failure');
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
        return null;
    }
}
