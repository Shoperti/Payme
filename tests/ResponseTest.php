<?php

namespace Shoperti\Tests\PayMe;

use Shoperti\PayMe\Response;

class ResponseTest extends \PHPUnit_Framework_TestCase
{
    /** @test */
    public function it_can_map_array_to_values()
    {
        $raw = [
            'foo' => 'bar',
        ];

        $transaction = new Response();

        $transaction->map($raw);

        $this->assertEquals($transaction->foo, 'bar');
    }

    /** @test */
    public function it_can_response_success()
    {
        $transaction = new Response();

        $transaction->map([
            'success' => true,
        ]);

        $this->assertTrue($transaction->success());

        $transaction->map([
            'success' => false,
        ]);

        $this->assertFalse($transaction->success());
    }

    /** @test */
    public function it_returns_a_valid_response()
    {
        $transaction = new Response();

        $data = [
            'isRedirect'      => false,
            'success'         => true,
            'reference'       => '123',
            'message'         => 'foo',
            'test'            => false,
            'authorization'   => '00000',
            'status'          => 'paid',
            'errorCode'       => 'card_declined',
            'type'            => 'charge',
        ];

        $transaction->setRaw($data)->map($data);

        $this->assertFalse($transaction->isRedirect());
        $this->assertTrue($transaction->success());
        $this->assertEquals($transaction->message(), 'foo');
        $this->assertFalse($transaction->test());
        $this->assertEquals($transaction->authorization(), '00000');
        $this->assertEquals($transaction->status(), 'paid');
        $this->assertEquals($transaction->reference(), '123');
        $this->assertEquals($transaction->errorCode(), 'card_declined');
        $this->assertEquals($transaction->type(), 'charge');
        $this->assertEquals($transaction->data(), $data);
    }
}
