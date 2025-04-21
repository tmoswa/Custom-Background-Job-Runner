<?php

namespace Tests\Feature\Commands;

use App\Models\BackgroundJobLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

/**
 * Feature tests for the ProcessBackgroundJobs command.
 */
class ProcessBackgroundJobsTest extends TestCase
{
    use RefreshDatabase;


    public function it_processes_pending_job()
    {
        Log::shouldReceive('channel->info')->times(3); // Start, processing, completed
        Log::shouldReceive('channel->debug')->atLeast()->once();

        $job = BackgroundJobLog::create([
            'class' => 'App\Jobs\SampleJob',
            'method' => 'process',
            'parameters' => ['test1', 'test2'],
            'status' => 'pending',
            'priority' => 1,
            'scheduled_at' => null,
        ]);

        // Run command in a controlled way
        $this->artisan('jobs:process')->expectsOutput('No pending jobs found, sleeping for 5 seconds');

        $this->assertEquals('completed', $job->fresh()->status);
        $this->assertNotNull($job->fresh()->completed_at);
    }


    public function it_handles_invalid_parameters()
    {
        Log::shouldReceive('channel->info')->once(); // Start
        Log::shouldReceive('channel->error')->once();

        $job = BackgroundJobLog::create([
            'class' => 'App\Jobs\SampleJob',
            'method' => 'process',
            'parameters' => 'invalid',
            'status' => 'pending',
            'priority' => 1,
            'scheduled_at' => null,
        ]);

        $this->artisan('jobs:process');

        $this->assertEquals('failed', $job->fresh()->status);
        $this->assertNotNull($job->fresh()->error_message);
    }


    public function it_respects_scheduled_at()
    {
        Log::shouldReceive('channel->info')->once(); // Start
        Log::shouldReceive('channel->debug')->atLeast()->once();

        $job = BackgroundJobLog::create([
            'class' => 'App\Jobs\SampleJob',
            'method' => 'process',
            'parameters' => ['test1', 'test2'],
            'status' => 'pending',
            'priority' => 1,
            'scheduled_at' => now()->addHour(),
        ]);

        $this->artisan('jobs:process');

        $this->assertEquals('pending', $job->fresh()->status);
    }
}
