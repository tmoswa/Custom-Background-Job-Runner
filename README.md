# Custom Background Job Runner for Laravel

![PHP](https://img.shields.io/badge/PHP-8.2+-blue.svg)
![Laravel](https://img.shields.io/badge/Laravel-11.x-red.svg)
![Docker](https://img.shields.io/badge/Docker-Supported-blue.svg)
![Kubernetes](https://img.shields.io/badge/Kubernetes-Ready-green.svg)

This project delivers a **custom background job runner** for Laravel, designed to execute PHP classes as background jobs without relying on Laravel's built-in queue system. It is a scalable, secure, and platform-independent solution, featuring a React-based web dashboard, Docker/Kubernetes deployment, and advanced features like job priorities, delays, and chained jobs. Built to exceed the requirements of the Laravel Coding Challenge for Senior Backend Engineers, this production-ready system includes comprehensive documentation and robust testing.

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Setup Instructions](#setup-instructions)
- [Usage](#usage)
- [Web Dashboard](#web-dashboard)
- [Security](#security)
- [Scalability](#scalability)
- [Testing](#testing)
- [Docker and Kubernetes Deployment](#docker-and-kubernetes-deployment)
- [Configuration](#configuration)
- [Advanced Features](#advanced-features)
- [Assumptions and Limitations](#assumptions-and-limitations)
- [Future Improvements](#future-improvements)
- [Sample Logs](#sample-logs)
- [Screenshots](#Screenshots)

## Features

- **CLI Script (`run-job.php`)**: Execute jobs via `php public/run-job.php ClassName methodName "param1,param2" [priority]` for dynamic class/method execution.
- **Global Helper**: `runBackgroundJob($class, $method, $params, $priority)` triggers jobs cross-platform (Windows/Unix).
- **Error Handling**: Logs exceptions to `storage/logs/background_jobs_errors.log`.
- **Retry Mechanism**: Configurable retries with delays, supporting exception type-based logic.
- **Logging**: Detailed job status logs (running, completed, failed) in `storage/logs/background_jobs.log`.
- **Security**: Restricts execution to pre-approved classes/methods via `config/background-jobs.php`.
- **Web Dashboard**: React-based UI to monitor job queue, status, retries, logs, and cancel jobs.
- **Job Priorities**: Higher-priority jobs execute first, configurable via CLI or helper.
- **Job Delays**: Supports delayed execution via `scheduled_at` in `BackgroundJobLog`.
- **Chained Jobs**: Automatically triggers follow-up jobs (e.g., `SampleJob::process` chains to `sendEmail`).
- **Scalability**: Dockerized with Nginx/PHP and Kubernetes-ready for horizontal scaling.
- **Testing**: Comprehensive tests for job execution, retries, and dashboard functionality.
- **Bonus Features**: Exception type-based retries, cross-platform compatibility, and extensible codebase.

## Requirements

- **PHP**: 8.2 or higher
- **Laravel**: 11.x
- **Composer**: Latest version
- **Node.js**: 18.x (for React frontend)
- **Docker**: For containerized deployment
- **Kubernetes**: Optional, for orchestration
- **Database**: MySQL or PostgreSQL (for `BackgroundJobLog` storage)

## Setup Instructions

1. **Clone the Repository**:
   ```bash
   https://github.com/tmoswa/Custom-Background-Job-Runner.git
   cd Custom-Background-Job-Runner


2. **Install Dependencies**:
    ```bash
    composer install
    npm install


3. **Configure Environment**:
Copy .env.example to .env:
    ```bash
    cp .env.example .env

4. **Update .env with database and job settings**:
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=job_runner
DB_USERNAME=root
DB_PASSWORD=
JOB_MAX_RETRIES=3
JOB_RETRY_DELAY=10
JOB_DEFAULT_PRIORITY=1


5. **Run Migrations**:
    ```bash
    php artisan migrate
   
- you may run into the question "The SQLite database configured for this application does not exist: database/database.sqlite." if you did not change anything in the .env, choose Yes

6. **Build Frontend**:
    ```bash
    npm run build
    or 
    npm run dev


7. **Start Server**:
    ```bash
    php artisan serve


8. **Run Queue Processor**:
Process jobs in priority order:
    ```bash
    php artisan jobs:process


9. **Run Tests (see Testing (#testing))**:
    ```bash
    php artisan migrate:fresh --env=testing
    then
    php artisan test


## Usage
10. **CLI Script**
Run a job via the CLI
    ```bash
    php public/run-job.php "App\Jobs\SampleJob" process test11,test10 4
    php public/run-job.php "App\Jobs\SampleJob" process test12,test13 0
    php public/run-job.php "App\Jobs\FailingJob" process test14,test15 2


- ClassName: Fully qualified class (e.g., App\Jobs\SampleJob).
- methodName: Method to execute (e.g., process).
- param1,param2: Comma-separated parameters.
- priority: Optional priority (default: 1).



11. **Global Helper**
Use the runBackgroundJob helper in Laravel:
            
        runBackgroundJob('App\Jobs\SampleJob', 'process', ['test1', 'test2'], 4);


12. **Triggers the CLI script in the background.**
Works on Windows and Unix-based systems.

12. **Example**
    ```bash
    php public/run-job.php "App\Jobs\SampleJob" process test11,test10 4
    php public/run-job.php "App\Jobs\FailingJob" process test14,test15 2

- SampleJob runs for 10 seconds (JOB_RETRY_DELAY), then completes.
- FailingJob fails after 3 attempts (JOB_MAX_RETRIES).

## Web Dashboard
- A React-based dashboard is available at http://127.0.0.1:8000/jobs:
**Features:**
- Displays job queue with id, class, method, parameters, status, priority, attempts, and error_message.
- Filters jobs by status (pending, running, completed, failed).
- Shows retry counts and error logs.
- Allows cancellation of running jobs, updating status to failed.
- Includes a "View Logs" page (/jobs/logs) for the last 10 log entries.

**Access**:
- Requires authentication (see Security (#security)).
- Navigate to /login, then /jobs.

## Security

12. **Job Execution**:
Only pre-approved classes/methods in config/background-jobs.php are executable:
    ```bash
    'allowed_jobs' => [
    'App\Jobs\SampleJob' => ['process', 'sendEmail'],
    'App\Jobs\FailingJob' => ['process', 'sendEmail'],
    ],

- Input validation in run-job.php prevents invalid class/method execution.

- Parameters are sanitized and stored as JSON in BackgroundJobLog.

API Security:
- /api/jobs and /api/jobs/{job} endpoints require authenticated requests.
- Rate limiting applied via Laravel middleware (throttle:60,1).

Frontend Security:
- CSRF protection enabled for all POST/DELETE requests (e.g., cancellation).
- Protected by Laravel’s built-in authentication (auth middleware).
- Removed Laravel Sanctum to simplify the frontend. (see Future Improvements (#future-improvements))
- Reintroduce JWT for API authentication (see Future Improvements (#future-improvements)).


## Scalability
*The system is designed for scalability*:
- Database: BackgroundJobLog model stores job metadata, optimized with indexes on status, priority, and scheduled_at.

- Queue Processing: Custom jobs:process command processes jobs in priority order, supporting horizontal scaling via multiple workers.

Docker:
- docker/nginx and docker/php folders provide containerized environments.

- Nginx serves the React frontend and proxies PHP requests.

- PHP-FPM handles job processing with optimized configurations.

Kubernetes:
- docker/kubernetes folder includes manifests for deploying to Kubernetes.

- Supports auto-scaling of PHP workers based on job volume.

- Redis or database locks (redis_lock_key) prevent race conditions in distributed environments.

- Load Balancing: Nginx and Kubernetes ingress balance traffic across pods.

- Monitoring: Logs and dashboard provide real-time job status for operational scaling.

## Testing
Automated tests ensure system reliability:
14. **Run Tests**:
    ```bash
    php artisan test
Tests cover:
- Job execution (SampleJob, FailingJob).
- Retry mechanism (max retries, delays).
- Priority ordering.
- Cancellation functionality.
- Security (unauthorized job rejection).
- Dashboard API endpoints.

Setup:
- Tests run automatically after composer install and php artisan migrate.

- Uses in-memory SQLite for faster test execution (phpunit.xml).

15. **Example Test**:
    ```bash
    // tests/Feature/JobRunnerTest.php
    public function testSampleJobCompletesSuccessfully()
    {
    $jobLog = BackgroundJobLog::create([...]);
    $processor = app(JobProcessor::class);
    $processor->execute('App\Jobs\SampleJob', 'process', ['test1', 'test2'], $jobLog);
    $this->assertEquals('completed', $jobLog->fresh()->status);
    }



## Docker and Kubernetes Deployment
16. **Docker Setup**:
    ```bash
    docker-compose up -d

- Builds Nginx and PHP containers.

- Exposes http://localhost:84/jobs for the dashboard.

- Configures PHP-FPM for job processing.



17. **Kubernetes Deployment**:
    ```bash
    kubectl apply -f docker/kubernetes/

- Deploys Laravel app, MySQL, and Redis.

- Configures HorizontalPodAutoscaler for PHP workers.

- Uses Ingress for load balancing.

**Configuration**:
- Update docker/nginx/nginx.conf for custom domains.

- Adjust docker/kubernetes/deployment.yaml for resource limits.



## Configuration
18. **Edit config/background-jobs.php**:
    ```bash
    return [
    'allowed_jobs' => [
    'App\Jobs\SampleJob' => ['process', 'sendEmail'],
    'App\Jobs\FailingJob' => ['process', 'sendEmail'],
    ],
    'max_retries' => env('JOB_MAX_RETRIES', 3),
    'retry_delay' => env('JOB_RETRY_DELAY', 10),
    'default_priority' => env('JOB_DEFAULT_PRIORITY', 1),
    'log_file' => storage_path('logs/background_jobs.log'),
    'error_log_file' => storage_path('logs/background_jobs_errors.log'),
    'exception_retries' => [
    'Illuminate\Database\QueryException' => 2,
    'Exception' => 1,
    ],
    'redis_lock_key' => 'background_job_lock',
    ];

- Set JOB_MAX_RETRIES, JOB_RETRY_DELAY, and JOB_DEFAULT_PRIORITY in .env.

- Add new jobs to allowed_jobs for execution.

## Advanced Features
**Chained Jobs**: SampleJob::process chains to sendEmail via getNextJob().

**Exception Type-Based Retries**: Configurable retries per exception type in exception_retries.

**Job Delays**: Set scheduled_at in BackgroundJobLog for delayed execution:

            $process->setWorkingDirectory(base_path());
            if ($delay > 0) {
                sleep($delay); // Simplified; will use a scheduler in production
            }
            $process->start();


**Symfony Process for Cross-Platform Execution:**
- Background jobs are executed asynchronously using the Symfony Process component. The runBackgroundJob helper launches php run-job.php <class> <method> <params> in a separate process:
    ````bash
    use Symfony\Component\Process\Process;
    $process = new Process(['php', base_path('run-job.php'), $class, $method, ...$params]);
    $process->setWorkingDirectory(base_path())->setTimeout(3600)->start();

- Process status is checked with isRunning(), and errors are logged to background_jobs_errors.

- Job Cancellation: The React dashboard (built with Tailwind CSS and SCSS) allows canceling running jobs, updating BackgroundJobLog status to failed. Real-time status checks are implemented via polling, with Redis locks ensuring thread safety.

- Robust Exception Handling: Comprehensive error handling in runBackgroundJob, JobProcessor, and RetryStrategy ensures job failures are logged, and statuses are updated in BackgroundJobLog.




## Assumptions and Limitations
**Assumptions**:
- Jobs are idempotent to handle retries safely.

- MySQL is used for BackgroundJobLog (configurable for other databases).

- React frontend requires Node.js for build.

**Limitations**:
- No real-time job progress updates (polling-based dashboard).

- Single queue processor; multiple workers require Redis locks.

- Limited to pre-approved jobs for security.

## Future Improvements
- Add WebSocket support for real-time job updates in the dashboard.

- Implement multi-queue support for different job types.

- Reintroduce Laravel Sanctum or JWT for API authentication.

- Add job timeout mechanism to prevent long-running jobs.

- Enhance monitoring with Prometheus/Grafana for job metrics.

- Use of Enums for Status and Priority

## Sample Logs
storage/logs/background_jobs.log:

    
    [2025-04-21 21:17:01] local.INFO: Executing job: App\Jobs\SampleJob::process {"params":["test11","test10"]}
    [2025-04-21 21:17:18] local.INFO: Job status updated: App\Jobs\SampleJob::handle {"status":"running","job_id":107}
    [2025-04-21 21:18:17] local.INFO: Job cancelled: 107 {"class":"App\\Jobs\\SampleJob","method":"process"}
    [2025-04-21 21:17:17] local.ERROR: Exception in job: Job cancelled by user
    [2025-04-21 21:17:18] local.INFO: Executing job: App\Jobs\FailingJob::process {"params":["test14","test15"]}
    [2025-04-21 21:18:17] local.ERROR: Job 108 failed after 3 attempts: Simulated job failure



## Screenshots

### Screenshot 1
![Screenshot 1](screenshots/Screenshot%202025-04-22%20at%2010.56.11.png)

### Screenshot 2
![Screenshot 2](screenshots/Screenshot%202025-04-22%20at%2010.56.23.png)

### Screenshot 3
![Screenshot 3](screenshots/Screenshot%202025-04-22%20at%2010.56.35.png)
