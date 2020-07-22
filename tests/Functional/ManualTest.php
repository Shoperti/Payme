<?php

namespace Shoperti\Tests\PayMe\Functional;

use Shoperti\PayMe\Gateways\Manual\Charges;
use Shoperti\PayMe\Gateways\Manual\ManualGateway;

class ManualTest extends AbstractFunctionalTestCase
{
    protected $gatewayData = [
        'config'  => 'manual',
        'gateway' => ManualGateway::class,
        'charges' => Charges::class,
    ];

    /** @test */
    public function it_should_succeed_to_perform_a_charge()
    {
        $charge = $this->getPayMe()->charges()->create(1000, 'payment');

        $this->assertTrue($charge->success());
        $this->assertEquals('charge', $charge->type());
        $this->assertEquals('pending', $charge->status());
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
