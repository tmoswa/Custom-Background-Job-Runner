<?php

namespace Tests\Stubs;

class TestSampleJob
{
    public function process($param1, $param2)
    {
        return true;
    }

    public function getNextJob()
    {
        return [
            'class' => self::class,
            'method' => 'sendEmail',
            'params' => ['test@example.com'],
        ];
    }

    public function sendEmail($email)
    {
        return true;
    }
    public function invalidMethod()
    {
        //
    }

}
