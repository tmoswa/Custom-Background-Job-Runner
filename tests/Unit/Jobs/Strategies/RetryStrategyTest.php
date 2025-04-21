<?php

namespace Tests\Unit\Jobs\Strategies;

use App\Jobs\Strategies\RetryStrategy;
use App\Models\BackgroundJobLog;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RetryStrategyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_retries_recoverable_exception()
    {
        $jobLog = BackgroundJobLog::factory()->create([
            'attempts' => 0,
            'status' => 'pending',
        ]);
        $exception = new Exception('Recoverable error');
        $strategy = new RetryStrategy();

        $called = false;
        $strategy->handle($jobLog, $exception, function () use (&$called) {
            $called = true;
        });

        $this->assertTrue($called, 'Callback should be called for retry');
        $this->assertEquals(1, $jobLog->fresh()->attempts);
        $this->assertEquals('pending', $jobLog->fresh()->status);
    }

    #[Test]
    public function it_fails_after_max_retries()
    {
        $jobLog = BackgroundJobLog::factory()->create([
            'attempts' => 0,
            'status' => 'pending',
            'error_message' => null,
        ]);
        $exception = new Exception('Test exception');
        $strategy = new RetryStrategy();

        // Simulate 2 retries where callback is called
        for ($i = 1; $i <= 2; $i++) {
            $callbackCalled = false;
            $strategy->handle($jobLog, $exception, function () use (&$callbackCalled) {
                $callbackCalled = true;
            });
            $jobLog->refresh();
            $this->assertTrue($callbackCalled, "Callback should be called on attempt $i");
            $this->assertEquals($i, $jobLog->attempts, "Attempt $i should increment correctly");
            $this->assertEquals('pending', $jobLog->status, "Status should remain pending after attempt $i");
            $this->assertEquals('Test exception', $jobLog->error_message, "Error message should be set");
        }

        // Third attempt should fail without calling callback
        $callbackCalled = false;
        $strategy->handle($jobLog, $exception, function () use (&$callbackCalled) {
            $callbackCalled = true;
        });

        $jobLog->refresh();
        $this->assertFalse($callbackCalled, 'Callback should not be called on final attempt');
        $this->assertEquals('failed', $jobLog->status, 'Job should be marked as failed after max retries');
        $this->assertEquals(3, $jobLog->attempts, 'Attempts should be 3 (incremented up to max)');
        $this->assertEquals('Test exception', $jobLog->error_message, 'Error message should be set');
        $this->assertNotNull($jobLog->completed_at, 'Completed_at should be set');
    }
}
