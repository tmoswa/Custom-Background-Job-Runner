<?php

namespace Database\Factories;

use App\Models\BackgroundJobLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BackgroundJobLog>
 */
class BackgroundJobLogFactory extends Factory
{
    protected $model = BackgroundJobLog::class;

    public function definition()
    {
        return [
            'class' => 'App\\Jobs\\SampleJob',
            'method'=>'process',
            'parameters' => [],
            'status' => 'pending',
            'priority'=> 1,
            'attempts' => 0,
            'error_message' => null,
            'scheduled_at' => now(),
            'started_at' => null,
            'completed_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
