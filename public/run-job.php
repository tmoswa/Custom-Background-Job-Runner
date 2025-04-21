<?php

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\BackgroundJobLog;
use App\Services\JobProcessor;
use Illuminate\Support\Facades\App;

/**
 * CLI script to execute a background job.
 *
 * Called by the runBackgroundJob helper or manually to process a job.
 * Creates a BackgroundJobLog entry for tracking job execution.
 *
 * Usage: php run-job.php <fully-qualified-class> <method> [<comma-separated-params>]
 *
 * @example php run-job.php App\Jobs\SampleJob process test1,test2
 * @throws Exception If the class, method, or job execution fails.
 */
$class = $argv[1] ?? null;
$method = $argv[2] ?? null;
$params = isset($argv[3]) ? explode(',', $argv[3]) : [];

if (!$class || !$method) {
    echo "Usage: php run-job.php <fully-qualified-class> <method> [<comma-separated-params>]\n";
    echo "Example: php run-job.php App\\Jobs\\SampleJob process test1,test2\n";
    exit(1);
}

// Normalize class name to use backslashes
$class = str_replace(['\\', '/'], '\\', $class);

// Validate class existence
if (!class_exists($class)) {
    echo "Error: Class '$class' does not exist.\n";
    exit(1);
}

// Validate method existence
if (!method_exists($class, $method)) {
    echo "Error: Method '$method' does not exist in class '$class'.\n";
    exit(1);
}

try {
    // Create a BackgroundJobLog entry if none exists
    $jobLog = BackgroundJobLog::firstOrCreate(
        [
            'class' => $class,
            'method' => $method,
            'parameters' => $params,  // Store as array
        ],
        [
            'status' => 'pending',
            'priority' => 1,
            'scheduled_at' => null,
        ]
    );

    $processor = App::make(JobProcessor::class);
    $processor->execute($class, $method, $params);
} catch (Exception $e) {
    echo "Error: {$e->getMessage()}\n";
    if ($jobLog) {
        $jobLog->update([
            'status' => 'failed',
            'error_message' => $e->getMessage(),
        ]);
    }
    exit(1);
}
