<?php

namespace Shoperti\Tests\PayMe;

use Shoperti\PayMe\ErrorCode;

class ErrorCodeTest extends \PHPUnit_Framework_TestCase
{
    /** @test */
    public function it_returns_a_valid_status()
    {
        $status = new ErrorCode('card_declined');

        $this->assertEquals((string) $status, 'card_declined');
    }

    /**
     * @test
     * @expectedException InvalidArgumentException
     */
    public function it_throws_exception_when_invalid_status()
    {
        $status = new ErrorCode('foo');
    }
}
