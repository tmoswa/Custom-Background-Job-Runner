<?php

namespace App\Contracts;

interface JobProcessorInterface
{
    public function execute(string $class, string $method, array $params): void;
}
