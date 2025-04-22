<?php
namespace Tests\Unit\Services;

use Tests\Stubs\TestSampleJob;
use App\Models\BackgroundJobLog;
use App\Services\JobProcessor;
use App\Services\Logger\FileLogger;
use App\Jobs\Strategies\RetryStrategy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Mockery;
use Illuminate\Support\Facades\DB;

class JobProcessorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['background-jobs.allowed_jobs' => [
            TestSampleJob::class => ['process', 'sendEmail', 'invalidMethod'],
        ]]);
    }


    public function it_executes_valid_job()
    {
        $logger = Mockery::mock(FileLogger::class);
        $retryStrategy = Mockery::mock(RetryStrategy::class);

        $sampleJob = Mockery::mock(TestSampleJob::class);
        $this->app->instance(TestSampleJob::class, $sampleJob);

        $sampleJob->shouldReceive('process')
            ->with('test1', 'test2')
            ->once()
            ->andReturn(true);

        $sampleJob->shouldReceive('getNextJob')
            ->once()
            ->andReturn([
                'class' => TestSampleJob::class,
                'method' => 'sendEmail',
                'params' => ['test@example.com'],
            ])
            ->ordered();

        $sampleJob->shouldReceive('getNextJob')
            ->once()
            ->andReturn(null)
            ->ordered();

        $sampleJob->shouldReceive('sendEmail')
            ->with('test@example.com')
            ->once()
            ->andReturn(true);

        $logger->shouldReceive('info')
            ->times(7) // 3 for process, 3 for sendEmail, 1 for creating sendEmail log
            ->withAnyArgs();

        $retryStrategy->shouldNotReceive('handle');

        $job = BackgroundJobLog::factory()->create([
            'class' => TestSampleJob::class,
            'method' => 'process',
            'parameters' => json_encode(['test1', 'test2']),
            'status' => 'pending',
            'priority' => 1,
        ]);

        $processor = new JobProcessor($logger, $retryStrategy);

        $processor->execute(TestSampleJob::class, 'process', ['test1', 'test2'], $job);

        // Force commit to ensure records are visible
        DB::commit();

        // Assert first job status
        $this->assertEquals('completed', $job->fresh()->status);
        $this->assertNotNull($job->fresh()->completed_at);

        // Debug: Dump all BackgroundJobLog records
        $allLogs = BackgroundJobLog::all()->toArray();
        if (empty($allLogs)) {
            $this->fail('No BackgroundJobLog records found.');
        }

        // Debug: Dump the expected parameters
        $expectedParameters = json_encode(['test@example.com']);

        // Query for follow-up job log with less restrictive conditions
        $followUpJob = BackgroundJobLog::where([
            'class' => TestSampleJob::class,
            'method' => 'sendEmail',
        ])->first();

        if (!$followUpJob) {
            $followUpJobByParams = BackgroundJobLog::where([
                'parameters' => json_encode(['test@example.com']),
            ])->first();
            if ($followUpJobByParams) {
                //dump('Found with parameters only:', $followUpJobByParams->toArray());
            }
        } else {
            //dump('Follow-up job found:', $followUpJob->toArray());
        }

        $this->assertNotNull($followUpJob, 'Follow-up job log was not created.');
        $this->assertEquals('completed', $followUpJob->status);
        $this->assertNotNull($followUpJob->completed_at);
    }


    public function it_throws_for_unauthorized_job()
    {
        $logger = Mockery::mock(FileLogger::class);
        $retryStrategy = Mockery::mock(RetryStrategy::class);

        $logger->shouldReceive('info')
            ->once()
            ->with(
                'Executing job: ' . TestSampleJob::class . '::unauthorizedMethod',
                Mockery::on(function ($params) {
                    return is_array($params) && isset($params['params']) && $params['params'] === ['test1', 'test2'];
                })
            );

        $logger->shouldReceive('error')
            ->once()
            ->withArgs(function ($message) {
                return strpos($message, 'Unauthorized job') !== false;
            });

        $retryStrategy->shouldNotReceive('handle');

        $processor = new JobProcessor($logger, $retryStrategy);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unauthorized job: ' . TestSampleJob::class . '::unauthorizedMethod');

        $processor->execute(TestSampleJob::class, 'unauthorizedMethod', ['test1', 'test2']);
    }



    public function it_retries_on_exception()
    {
        $logger = Mockery::mock(FileLogger::class);
        $retryStrategy = Mockery::mock(RetryStrategy::class);

        $sampleJob = Mockery::mock(TestSampleJob::class);
        $this->app->instance(TestSampleJob::class, $sampleJob);

        $sampleJob->shouldReceive('invalidMethod')
            ->once()
            ->andThrow(new \Exception('Method failed'));

        $logger->shouldReceive('info')
            ->twice() // Executing and Calling
            ->withAnyArgs();

        $logger->shouldReceive('error')
            ->once()
            ->withArgs(function ($message) {
                return strpos($message, 'Exception in job') !== false;
            });

        $job = BackgroundJobLog::factory()->create([
            'class' => TestSampleJob::class,
            'method' => 'invalidMethod',
            'parameters' => json_encode(['test1', 'test2']),
            'status' => 'pending',
            'priority' => 1,
        ]);

        $callbackCalled = false;
        $retryStrategy->shouldReceive('handle')
            ->once()
            ->withArgs(function ($jobArg, $exceptionArg, $callbackArg) use ($job, &$callbackCalled) {
                $this->assertEquals($job->id, $jobArg->id);
                $this->assertInstanceOf(\Exception::class, $exceptionArg);
                $this->assertEquals('Method failed', $exceptionArg->getMessage());
                $callbackCalled = true;
                return true; // Do not execute callback to avoid retry
            });

        $processor = new JobProcessor($logger, $retryStrategy);
        $processor->execute(TestSampleJob::class, 'invalidMethod', ['test1', 'test2'], $job); // Pass $job

        $this->assertTrue($callbackCalled, 'RetryStrategy callback should be called');
    }
    protected function tearDown(): void
    {
        if (function_exists('restore_error_handler')) {
            restore_error_handler();
        }

        if (function_exists('restore_exception_handler')) {
            restore_exception_handler();
        }

        Mockery::close();
        parent::tearDown();
    }
}
