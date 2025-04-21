<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BackgroundJobLog;
use App\Services\JobProcessor;
use Illuminate\Support\Facades\App;

/**
 * Test command to simulate run-job.php execution for testing purposes.
 *
 * @package App\Console\Commands
 */
class RunJobCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:run-job {class : The fully qualified class name} {method : The method to call} {params? : Comma-separated parameters}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Simulate run-job.php execution for testing';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $class = $this->argument('class');
        $method = $this->argument('method');
        $params = $this->argument('params') ? explode(',', $this->argument('params')) : [];

        if (!$class || !$method) {
            $this->error('Usage: php artisan command:run-job <class> <method> [<params>]');
            return 1;
        }

        $class = str_replace(['\\', '/'], '\\', $class);

        if (!class_exists($class)) {
            $this->error("Error: Class '$class' does not exist.");
            return 1;
        }

        if (!method_exists($class, $method)) {
            $this->error("Error: Method '$method' does not exist in class '$class'.");
            return 1;
        }

        try {
            $jobLog = BackgroundJobLog::firstOrCreate(
                [
                    'class' => $class,
                    'method' => $method,
                    'parameters' => json_encode($params),
                ],
                [
                    'status' => 'pending',
                    'priority' => 1,
                    'scheduled_at' => null,
                ]
            );

            $processor = App::make(JobProcessor::class);
            $processor->execute($class, $method, $params, $jobLog, 0);

            return 0;
        } catch (\Exception $e) {
            $this->error("Error: {$e->getMessage()}");
            if (isset($jobLog)) {
                $jobLog->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
            }
            return 1;
        }
    }
}
