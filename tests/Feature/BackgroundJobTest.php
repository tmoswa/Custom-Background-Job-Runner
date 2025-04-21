<?php

namespace Tests\Feature;

use App\Models\BackgroundJobLog;
use App\Services\Logger\FileLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Process\Process;
use Tests\TestCase;
use Mockery;

/**
 * Feature tests for the runBackgroundJob helper.
 */
class BackgroundJobTest extends TestCase
{
    use RefreshDatabase;

    protected $mockProcess;
    protected $logger;

    protected function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        $this->mockProcess = Mockery::mock(Process::class);
        $this->app->instance(Process::class, $this->mockProcess);
        $this->logger = Mockery::mock(FileLogger::class);
        $this->app->instance(FileLogger::class, $this->logger);
        Queue::fake();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        Mockery::close();
        restore_error_handler();
        restore_exception_handler();
        parent::tearDown();
    }

    #[Test]
    public function run_background_job_schedules_job()
    {
        config(['background-jobs.allowed_jobs' => [
            'App\Jobs\SampleJob' => ['process'],
        ]]);

        $this->logger->shouldReceive('info')->once()->withAnyArgs();

        $result = runBackgroundJob(
            'App\Jobs\SampleJob',
            'process',
            ['test1', 'test2'],
            0,
            1
        );

        DB::commit();

        $this->assertTrue($result);
        $job = BackgroundJobLog::first();
        $this->assertNotNull($job);
        $this->assertEquals('App\Jobs\SampleJob', $job->class);
        $this->assertEquals('process', $job->method);
        $this->assertEquals(['test1', 'test2'], $job->parameters);
        $this->assertEquals('pending', $job->status);
    }

    #[Test]
    public function unauthorized_job_fails()
    {
        config(['background-jobs.allowed_jobs' => [
            'App\Jobs\SampleJob' => ['process'],
        ]]);

        $this->logger->shouldReceive('error')->once()->withAnyArgs();

        $result = runBackgroundJob(
            'App\Jobs\InvalidJob',
            'process',
            ['test1', 'test2']
        );

        DB::commit();

        $this->assertFalse($result);
        $this->assertEmpty(BackgroundJobLog::all());
    }

    #[Test]
    public function it_handles_process_failure()
    {
        // Arrange: Configure the process to fail (e.g., invalid job class)
        $process = new Process(['php', 'run-job.php', 'InvalidJob', 'process', 'test1,test2']);
        $process->setTimeout(1); // Short timeout to force failure
        $process->start();
        $process->wait();
        $this->assertFalse($process->isRunning());
        $this->assertNotEquals(0, $process->getExitCode());
    }

    #[Test]
    public function it_respects_delay()
    {
        config(['background-jobs.allowed_jobs' => [
            'App\Jobs\SampleJob' => ['process'],
        ]]);

        $this->logger->shouldReceive('info')->once()->withAnyArgs();

        $result = runBackgroundJob(
            'App\Jobs\SampleJob',
            'process',
            ['test1', 'test2'],
            10,
            1
        );

        DB::commit();

        $this->assertTrue($result);
        $job = BackgroundJobLog::first();
        $this->assertNotNull($job);
        $this->assertGreaterThan(now()->subSeconds(1), $job->scheduled_at);
    }
}
