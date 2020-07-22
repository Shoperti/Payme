<?php

namespace Shoperti\Tests\PayMe\Functional;

use Shoperti\PayMe\Gateways\Bogus\BogusGateway;
use Shoperti\PayMe\Gateways\Bogus\Charges;

class BogusTest extends AbstractFunctionalTestCase
{
    protected $gatewayData = [
        'config'  => 'bogus',
        'gateway' => BogusGateway::class,
        'charges' => Charges::class,
    ];

    /** @test */
    public function it_should_succeed_to_charge_a_token()
    {
        $charge = $this->getPayMe()->charges()->create(1000, 'success');

        $this->assertTrue($charge->success());
    }

    /** @test */
    public function it_should_fail_to_charge_a_token()
    {
        $charge = $this->getPayMe()->charges()->create(1000, 'fail');

        $this->assertFalse($charge->success());
    }
}
