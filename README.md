# Laravel Custom Background Job Runner

A scalable, secure, and professional background job processing system built with **Laravel 11**, **PHP 8.3**, **ReactJS**, **Tailwind CSS**, and **SCSS**. Supports **monolithic**, **Docker**, and **Kubernetes** deployments, incorporating all **23 Gang of Four (GoF) Design Patterns** and **SOLID Principles**.

## Features
- **Custom Job Runner**: Execute PHP classes as background jobs via CLI or helper function.
- **Scalability**: Supports monolithic or containerized deployments with Kubernetes autoscaling.
- **Frontend**: ReactJS dashboard styled with Tailwind CSS and SCSS.
- **Security**: Whitelisted jobs, Sanctum authentication, Kubernetes RBAC.
- **Error Handling**: Configurable retries with detailed logging.
- **Logging**: Logs stored in `storage/logs/background_jobs.log` and `storage/logs/background_jobs_errors.log`.
- **Design Patterns**: Implements all 23 GoF patterns (e.g., Factory, Observer, Strategy).
- **SOLID Principles**: Adheres to Single Responsibility, Open/Closed, Liskov Substitution, Interface Segregation, and Dependency Inversion principles.
- **Chained Jobs**: Supports sequential job execution.
- **Job Delays and Priorities**: Configurable delays and priority-based execution.

## Prerequisites
- **PHP**: 8.2 or higher
- **Composer**: Latest version
- **Node.js**: 18.x or higher
- **Docker**: For Docker deployment
- **Minikube**: For Kubernetes deployment
- **kubectl**: For Kubernetes management
- **MySQL**: 8.0 or higher
- **Redis**: Latest version

## Setup

### Monolithic Deployment
1. **Clone Repository**:
   ```bash
   git clone https://github.com/tmoswa/job-runner.git
   cd job-runner

Install PHP Dependencies: composer install

Install Node.js Dependencies: npm install

Configure Environment:
Copy .env.example to .env: cp .env.example .env

Generate application key: php artisan key:generate

Update .env with your database and Redis credentials:
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel_job_runner
DB_USERNAME=root
DB_PASSWORD=

REDIS_HOST=127.0.0.1
REDIS_PORT=6379


Run Migrations:
php artisan serve

Build Frontend:
npm run build

Serve Application:
php artisan serve

Access at http://localhost:8000/jobs

Run Job Processor: php artisan jobs:process

Docker Deployment
Build and Run Containers: docker-compose up -d

Run Migrations:
docker-compose exec php php artisan migrate

Access Application:
Visit http://localhost/jobs.

Run Job Processor:
docker-compose exec php php artisan jobs:process

Kubernetes Deployment
Start Minikube:minikube start

Build and Push Docker Image:
Update docker/kubernetes/deployment.yaml with your registry:
image: yourregistry/laravel-job-runner:latest

Build and push:
docker build -t yourregistry/laravel-job-runner:latest -f docker/php/Dockerfile .
docker push yourregistry/laravel-job-runner:latest

Create Secrets:
kubectl create secret generic laravel-secrets --from-literal=app-key=your-app-key
kubectl create secret generic mysql-secrets --from-literal=password=your-mysql-password

Apply Kubernetes Manifests:
kubectl apply -f docker/kubernetes/mysql.yaml
kubectl apply -f docker/kubernetes/redis.yaml
kubectl apply -f docker/kubernetes/

Access Application:
kubectl get svc laravel-job-runner
minikube service laravel-job-runner


Usage
Running a Job via CLI
php public/run-job.php "App\Jobs\SampleJob" process test1,test2

Running a Job via Helper
runBackgroundJob(App\Jobs\SampleJob::class, 'process', ['test1', 'test2'], delay: 30, priority: 2);

Accessing the Dashboard
Visit /jobs or /jobs/logs in the browser.
Configuration
Edit config/background-jobs.php to configure allowed jobs, retries, and logging:

'allowed_jobs' => [
'App\Jobs\SampleJob' => ['process', 'sendEmail'],
],
'max_retries' => 5,
'retry_delay' => 120,


Testing
Run Unit and Feature Tests:
php artisan test

Run Browser Tests (Dusk):
php artisan dusk

Generating Documentation
The codebase includes PHPDoc comments for automated documentation generation using PHPDocumentor.
Install PHPDocumentor:

composer require --dev phpdocumentor/phpdocumentor

Generate Documentation:
vendor/bin/phpdoc -d app -t docs/api

-d app: Scans the app directory for PHPDoc comments.

-t docs/api: Outputs documentation to the docs/api directory.

Access Documentation:
Open docs/api/index.html in a browser to view the generated API documentation.

Design Patterns
The application implements all 23 GoF Design Patterns, including:
Creational: Abstract Factory (job processors), Factory Method (logger instances).

Structural: Adapter (logger interface), Facade (runBackgroundJob helper).

Behavioral: Observer (JobLogObserver), Strategy (RetryStrategy), Command (ProcessBackgroundJobs).

SOLID Principles
Single Responsibility Principle (SRP): Each class has a single responsibility (e.g., JobLogObserver only logs).

Open/Closed Principle (OCP): Extensible retry strategies.

Liskov Substitution Principle (LSP): JobInterface implementations are interchangeable.

Interface Segregation Principle (ISP): Minimal interfaces (e.g., LoggerInterface).

Dependency Inversion Principle (DIP): Dependencies injected via interfaces.










License
MIT License


---

#### Commands for Running the Application

1. **Monolithic Deployment**:
   ```bash
   git clone https://github.com/yourusername/laravel-job-runner.git
   cd laravel-job-runner
   composer install
   npm install
   cp .env.example .env
   php artisan key:generate
   php artisan migrate
   npm run build
   php artisan serve
   php artisan jobs:process

Docker Deployment:

docker-compose up -d
docker-compose exec php php artisan migrate
docker-compose exec php php artisan jobs:process


Kubernetes Deployment:

minikube start
docker build -t yourregistry/laravel-job-runner:latest -f docker/php/Dockerfile .
docker push yourregistry/laravel-job-runner:latest
kubectl create secret generic laravel-secrets --from-literal=app-key=your-app-key
kubectl create secret generic mysql-secrets --from-literal=password=your-mysql-password
kubectl apply -f docker/kubernetes/mysql.yaml
kubectl apply -f docker/kubernetes/redis.yaml
kubectl apply -f docker/kubernetes/
kubectl get svc laravel-job-runner
minikube service laravel-job-runner



