<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Mockery;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        // Roll back all active transactions
        while (DB::transactionLevel() > 0) {
            DB::rollBack();
        }

        Mockery::close();
        parent::tearDown();
    }
}
