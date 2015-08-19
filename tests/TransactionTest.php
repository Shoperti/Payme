<?php

namespace Shoperti\Test\PayMe;

use Shoperti\PayMe\Transaction;

class TransactionTest extends \PHPUnit_Framework_TestCase
{
    /** @test */
    public function it_can_map_array_to_values()
    {
        $raw = [
            'foo' => 'bar',
        ];

        $transaction = new Transaction();

        $transaction->map($raw);

        $this->assertEquals($transaction->foo, 'bar');
    }

    /** @test */
    public function it_can_response_success()
    {
        $transaction = new Transaction();

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
        $transaction = new Transaction();

        $transaction->map([
            'isRedirect'      => false,
            'success'         => true,
            'message'         => 'foo',
            'test'            => false,
            'authorization'   => '1',
            'status'          => 'paid',
            'reference'       => '123',
            'code'            => '123',
        ]);

        $this->assertFalse($transaction->isRedirect());
        $this->assertTrue($transaction->success());
        $this->assertEquals($transaction->message(), 'foo');
        $this->assertFalse($transaction->test());
        $this->assertEquals($transaction->authorization(), '1');
        $this->assertEquals($transaction->status(), 'paid');
        $this->assertEquals($transaction->reference(), '123');
        $this->assertEquals($transaction->code(), '123');
    }
}
