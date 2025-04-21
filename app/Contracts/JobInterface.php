<?php

namespace App\Contracts;

/**
 * Interface for background job classes.
 *
 * Defines the contract for jobs that can be executed by the job processor,
 * including handling parameters and chaining subsequent jobs.
 *
 * @package App\Contracts
 */
interface JobInterface
{
    /**
     * Handle the job execution with given parameters.
     *
     * @param array $params Parameters for the job.
     * @return mixed The result of the job execution.
     */
    public function handle(array $params): mixed;

    /**
     * Get the next job to be executed in a chain.
     *
     * @return array|null Array containing class, method, and params, or null if no next job.
     */
    public function getNextJob(): ?array;
}
