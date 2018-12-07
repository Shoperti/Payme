<?php

namespace Shoperti\Tests\PayMe\Functional;

use Shoperti\PayMe\PayMe;
use Shoperti\PayMe\Gateways\SrPago\SrPagoGateway;

class SrPagoTest extends AbstractFunctionalTestCase
{
    /**
     * SrPago gateway
     *
     * @var 
     */
    protected $gateway;

    /** @test */
    public function it_creates_a_sr_pago_instance()
    {
        $gateway = PayMe::make($this->credentials['sr_pago']);

        $this->assertInstanceOf(SrPagoGateway::class, $gateway->getGateway());
    }

    /** @test */
    public function it_authenticates_current_application()
    {
        $gateway = PayMe::make($this->credentials['sr_pago']);

        $gateway->loginApplication();

        $this->assertNotNull($gateway->connectionToken());
    }
}
