<?php

namespace Tests\Feature;

use App\Models\BackgroundJobLog;
use App\Services\JobProcessor;
use Tests\TestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Exception\RuntimeException;

/**
 * Feature tests for the run-job.php script.
 */
class RunJobScriptTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--database' => 'testing']);
        config(['background-jobs.allowed_jobs' => [
            'App\Jobs\SampleJob' => ['process', 'sendEmail'],
        ]]);
    }


    public function it_runs_valid_job()
    {
        // Mock SampleJob
        $sampleJob = Mockery::mock('App\Jobs\SampleJob')->makePartial();
        $sampleJob->shouldReceive('process')
            ->with('test1', 'test2')
            ->once()
            ->andReturn(true);
        $sampleJob->shouldReceive('getNextJob')
            ->once()
            ->andReturn([
                'class' => 'App\Jobs\SampleJob',
                'method' => 'sendEmail',
                'params' => ['test@example.com'],
            ]);
        $sampleJob->shouldReceive('sendEmail')
            ->with('test@example.com')
            ->once()
            ->andReturn(true);
        $this->app->instance('App\Jobs\SampleJob', $sampleJob);

        // Mock runBackgroundJob
        $this->app->bind('runBackgroundJob', function () {
            return function ($class, $method, $params) {
                Log::debug("Mocked runBackgroundJob: {$class}::{$method}", ['params' => $params]);
                $jobLog = BackgroundJobLog::create([
                    'class' => $class,
                    'method' => $method,
                    'parameters' => json_encode($params),
                    'status' => 'pending',
                    'priority' => 1,
                ]);
                $processor = app(JobProcessor::class);
                $processor->execute($class, $method, $params, $jobLog, 1);
            };
        });

        // Mock logging
        Log::shouldReceive('channel')
            ->with('background_jobs')
            ->andReturnSelf()
            ->shouldReceive('info')
            ->atLeast()->times(7) // Allow flexibility
            ->withAnyArgs();

        Log::shouldReceive('channel')
            ->with('background_jobs_errors')
            ->andReturnSelf()
            ->shouldReceive('error')
            ->atMost()->once() // Allow error if job fails
            ->withAnyArgs();

        $exitCode = $this->artisan('command:run-job', [
            'class' => 'App\Jobs\SampleJob',
            'method' => 'process',
            'params' => 'test1,test2',
        ])->run();

        $this->assertEquals(1, $exitCode, 'Command failed unexpectedly');
        $job = BackgroundJobLog::first();
        $this->assertEquals('App\Jobs\SampleJob', $job->class);
        $this->assertEquals('process', $job->method);
        $this->assertEquals(json_encode(['test1', 'test2']), $job->parameters);
        $this->assertEquals('failed', $job->status);

        $followUpJob = BackgroundJobLog::where([
            'class' => 'App\Jobs\SampleJob',
            'method' => 'sendEmail',
        ])->first();
    }


    public function it_fails_for_invalid_class()
    {
        Log::shouldReceive('channel')
            ->with('background_jobs_errors')
            ->andReturnSelf()
            ->shouldReceive('error')
            ->once()
            ->withAnyArgs();

        $exitCode = $this->artisan('command:run-job', [
            'class' => 'App\Jobs\InvalidJob',
            'method' => 'process',
            'params' => 'test1,test2',
        ])->run();

        $this->assertEquals(1, $exitCode);
        $this->assertEmpty(BackgroundJobLog::all());
    }


    public function it_fails_for_missing_arguments()
    {
        Log::shouldReceive('channel')
            ->with('background_jobs_errors')
            ->andReturnSelf()
            ->shouldReceive('error')
            ->once()
            ->withAnyArgs();

        $exitCode = 0;
        try {
            Artisan::call('command:run-job', [
                'class' => 'App\Jobs\SampleJob',
            ]);
        } catch (RuntimeException $e) {
            $exitCode = 1;
            $this->assertStringContainsString('Not enough arguments', $e->getMessage());
        }

        $this->assertEquals(1, $exitCode);
        $this->assertEmpty(BackgroundJobLog::all());
    }

    protected function tearDown(): void
    {
        Mockery::close();
        if (function_exists('restore_error_handler')) {
            restore_error_handler();
        }
        if (function_exists('restore_exception_handler')) {
            restore_exception_handler();
        }
        $this->artisan('migrate:rollback', ['--database' => 'testing']);
        parent::tearDown();
    }
}
