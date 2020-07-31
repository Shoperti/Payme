<?php

namespace Shoperti\Tests\PayMe\Functional;

use Shoperti\PayMe\Gateways\Bogus\BogusGateway;

class BogusTest extends AbstractFunctionalTestCase
{
    protected $gatewayData = [
        'config'  => 'bogus',
        'gateway' => BogusGateway::class,
    ];

    /** @test */
    public function it_should_succeed_to_charge_a_token()
    {
        $charge = $this->successfulChargeRequest('success', 1000, null);

        $this->assertEquals('charge', $charge->type());
        $this->assertEquals('paid', $charge->status());
        $this->assertEquals('12345', $charge->reference());
        $this->assertEquals('123', $charge->authorization());
    }

    /** @test */
    public function it_should_fail_to_charge_a_token()
    {
        $charge = $this->chargeRequest('fail', 1000, null);

        $this->assertFalse($charge->success());
    }
}
