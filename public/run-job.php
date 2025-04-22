<?php

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
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
$priority = isset($argv[4]) ? (int)$argv[4] : config('background-jobs.default_priority', 1);

if (!$class || !$method) {
    echo "Usage: php run-job.php <fully-qualified-class> <method> [<comma-separated-params>] [<priority>]\n";
    echo "Example: php run-job.php App\\Jobs\\SampleJob process test1,test2 2\n";
    exit(1);
}

// Normalize class name
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
    // Create a BackgroundJobLog entry
    $jobLog = BackgroundJobLog::create([
        'class' => $class,
        'method' => $method,
        'parameters' => json_encode($params),
        'status' => 'pending',
        'priority' => $priority,
        'scheduled_at' => now(),
    ]);

    $processor = App::make(JobProcessor::class);
    $processor->execute($class, $method, $params, $jobLog);
} catch (Exception $e) {
    echo "Error: {$e->getMessage()}\n";
    if (isset($jobLog)) {
        $jobLog->update([
            'status' => 'failed',
            'error_message' => $e->getMessage(),
        ]);
    }
    exit(1);
}
