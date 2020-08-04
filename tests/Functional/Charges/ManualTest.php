<?php

namespace Shoperti\Tests\PayMe\Functional\Charges;

class ManualTest extends AbstractTest
{
    protected $gatewayData = [
        'config' => 'manual',
    ];

    /** @test */
    public function it_should_succeed_to_perform_a_charge()
    {
        $charge = $this->successfulChargeRequest('payment', 1000, null);

        $this->assertEquals('charge', $charge->type());
        $this->assertEquals('pending', $charge->status());
        $this->assertEquals(null, $charge->reference());
        $this->assertEquals(null, $charge->authorization());
    }

    /** @test */
    public function it_should_succeed_to_perform_a_completion()
    {
        $charge = $this->getPayMe()->charges()->complete(1000, 'payment');

        $this->assertTrue($charge->success());
        $this->assertEquals('charge', $charge->type());
        $this->assertEquals('paid', $charge->status());
    }

    /** @test */
    public function it_should_succeed_to_perform_a_refund()
    {
        $charge = $this->getPayMe()->charges()->refund(1000, 'reference');

        $this->assertTrue($charge->success());
        $this->assertEquals('refund', $charge->type());
        $this->assertEquals('refunded', $charge->status());
    }
}
