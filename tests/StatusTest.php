<?php

namespace Shoperti\Test\PayMe;

use Shoperti\PayMe\Status;

class StatusTest extends \PHPUnit_Framework_TestCase
{
    /** @test */
    public function it_returns_a_valid_status()
    {
        $status = new Status('paid');

        $this->assertEquals((string) $status, 'paid');
    }

    /**
     * @test
     * @expectedException InvalidArgumentException
     */
    public function it_throws_exception_when_invalid_status()
    {
        $status = new Status('foo');
    }
}
