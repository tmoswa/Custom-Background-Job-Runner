<?php

namespace App\Providers;

use App\Contracts\LoggerInterface;
use App\Models\BackgroundJobLog;
use App\Observers\JobLogObserver;
use App\Services\Logger\FileLogger;
use Illuminate\Support\ServiceProvider;

/**
 * Service provider for background job functionality.
 *
 * Registers logger bindings and sets up job observers.
 *
 * @package App\Providers
 */
class JobServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * Binds LoggerInterface to FileLogger.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->bind(LoggerInterface::class, FileLogger::class);
    }

    /**
     * Bootstrap services.
     *
     * Attaches JobLogObserver to BackgroundJobLog model.
     *
     * @return void
     */
    public function boot(): void
    {
        BackgroundJobLog::observe(JobLogObserver::class);
    }
}
