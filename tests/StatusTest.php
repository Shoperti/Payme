<?php

namespace Shoperti\Tests\PayMe;

use Shoperti\PayMe\Status;

class StatusTest extends \PHPUnit_Framework_TestCase
{
    /** @test */
    public function it_returns_a_valid_status()
    {
        $status = new Status('paid');

        $this->assertSame('paid', (string) $status);
    }

    /**
     * @test
     *
     * @expectedException InvalidArgumentException
     */
    public function it_throws_exception_when_invalid_status()
    {
        new Status('foo');
    }
}
