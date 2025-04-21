<?php

namespace Tests\Unit\Models;

use App\Models\BackgroundJobLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit tests for the BackgroundJobLog model.
 */
class BackgroundJobLogTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_casts_parameters_as_array()
    {
        $job = BackgroundJobLog::create([
            'class' => 'App\Jobs\SampleJob',
            'method' => 'process',
            'parameters' => ['test1', 'test2'],
            'status' => 'pending',
            'priority' => 1,
        ]);

        $this->assertIsArray($job->parameters);
        $this->assertEquals(['test1', 'test2'], $job->parameters);
    }

    #[Test]
    public function it_handles_null_scheduled_at()
    {
        $job = BackgroundJobLog::create([
            'class' => 'App\Jobs\SampleJob',
            'method' => 'process',
            'parameters' => ['test1', 'test2'],
            'status' => 'pending',
            'priority' => 1,
            'scheduled_at' => null,
        ]);

        $this->assertNull($job->scheduled_at);
    }

    #[Test]
    public function it_increments_attempts()
    {
        $job = BackgroundJobLog::create([
            'class' => 'App\Jobs\SampleJob',
            'method' => 'process',
            'parameters' => ['test1', 'test2'],
            'status' => 'pending',
            'priority' => 1,
        ]);

        $job->increment('attempts');
        $this->assertEquals(1, $job->fresh()->attempts);
    }

    #[Test]
    public function it_filters_pending_jobs()
    {
        BackgroundJobLog::create([
            'class' => 'App\Jobs\SampleJob',
            'method' => 'process',
            'parameters' => ['test1', 'test2'],
            'status' => 'pending',
            'priority' => 1,
        ]);

        BackgroundJobLog::create([
            'class' => 'App\Jobs\SampleJob',
            'method' => 'process',
            'parameters' => ['test3', 'test4'],
            'status' => 'completed',
            'priority' => 1,
        ]);

        $pendingJobs = BackgroundJobLog::where('status', 'pending')->get();
        $this->assertCount(1, $pendingJobs);
        $this->assertEquals(['test1', 'test2'], $pendingJobs[0]->parameters);
    }
}
