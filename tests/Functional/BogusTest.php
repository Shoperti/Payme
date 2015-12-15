<?php

namespace Shoperti\Tests\PayMe\Functional;

use Shoperti\PayMe\PayMe;

class BogusTest extends AbstractFunctionalTestCase
{
    /** @test */
    public function it_should_create_a_new_bogus_gateway()
    {
        $gateway = PayMe::make($this->credentials['bogus']);

        $this->assertInstanceOf('Shoperti\PayMe\Gateways\Bogus\BogusGateway', $gateway->getGateway());
        $this->assertInstanceOf('Shoperti\PayMe\Gateways\Bogus\Charges', $gateway->charges());
    }

    /** @test */
    public function is_should_succeed_to_charge_a_token()
    {
        $gateway = PayMe::make($this->credentials['bogus']);

        $charge = $gateway->charges()->create(1000, 'success');

        $this->assertTrue($charge->success());
    }

    /** @test */
    public function is_should_fail_to_charge_a_token()
    {
        $gateway = PayMe::make($this->credentials['bogus']);

        $charge = $gateway->charges()->create(1000, 'fail');

        $this->assertFalse($charge->success());
    }
}
