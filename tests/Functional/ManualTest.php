<?php

namespace Shoperti\Tests\PayMe\Functional;

use Shoperti\PayMe\PayMe;

class ManualTest extends AbstractFunctionalTestCase
{
    /** @test */
    public function it_should_create_a_new_manual_gateway()
    {
        $gateway = PayMe::make($this->credentials['manual']);

        $this->assertInstanceOf('Shoperti\PayMe\Gateways\Manual\ManualGateway', $gateway->getGateway());
        $this->assertInstanceOf('Shoperti\PayMe\Gateways\Manual\Charges', $gateway->charges());
    }

    /** @test */
    public function it_should_succeed_to_perform_a_charge()
    {
        $gateway = PayMe::make($this->credentials['manual']);

        $charge = $gateway->charges()->create(1000, 'payment');

        $this->assertTrue($charge->success());
        $this->assertEquals('charge', $charge->type());
        $this->assertEquals('pending', $charge->status());
    }

    /** @test */
    public function it_should_succeed_to_perform_a_completion()
    {
        $gateway = PayMe::make($this->credentials['manual']);

        $charge = $gateway->charges()->complete(1000, 'payment');

        $this->assertTrue($charge->success());
        $this->assertEquals('charge', $charge->type());
        $this->assertEquals('paid', $charge->status());
    }

    /** @test */
    public function it_should_succeed_to_perform_a_refund()
    {
        $gateway = PayMe::make($this->credentials['manual']);

        $charge = $gateway->charges()->refund(1000, 'reference');

        $this->assertTrue($charge->success());
        $this->assertEquals('refund', $charge->type());
        $this->assertEquals('refunded', $charge->status());
    }
}
