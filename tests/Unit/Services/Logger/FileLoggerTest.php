<?php

namespace Tests\Unit\Services\Logger;

use App\Services\Logger\FileLogger;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FileLoggerTest extends TestCase
{
    #[Test]
    public function it_logs_info_to_background_jobs_channel()
    {
        Log::shouldReceive('channel')
            ->once()
            ->with('background_jobs')
            ->andReturnSelf()
            ->shouldReceive('info')
            ->once()
            ->with('Test message', ['context' => 'value']);

        $logger = new FileLogger();
        $logger->info('Test message', ['context' => 'value']);

        $this->assertTrue(true);
    }

    #[Test]
    public function it_logs_error_to_background_jobs_errors_channel()
    {
        Log::shouldReceive('channel')
            ->once()
            ->with('background_jobs_errors')
            ->andReturnSelf()
            ->shouldReceive('error')
            ->once()
            ->with('Error message', ['context' => 'value']);

        $logger = new FileLogger();
        $logger->error('Error message', ['context' => 'value']);

        $this->assertTrue(true);
    }
}
