<?php

namespace Shoperti\Tests\PayMe\Functional;

use Shoperti\PayMe\Gateways\MercadoPagoBasic\Charges;
use Shoperti\PayMe\Gateways\MercadoPagoBasic\MercadoPagoBasicGateway;

class MercadoPagoBasicTest extends AbstractFunctionalTestCase
{
    protected $gatewayData = [
        'config'  => 'mercadopago_basic',
        'gateway' => MercadoPagoBasicGateway::class,
        'charges' => Charges::class,
    ];

    /** @test */
    public function it_should_succeed_to_create_a_charge()
    {
        $order = $this->getOrderData();
        $payload = $order['payload'];
        $amount = $order['total'];

        $charge = $this->getPayMe()->charges()->create($amount, 'regular_payment', $payload);

        $this->assertFalse($charge->success());
        $this->assertTrue($charge->isRedirect());
        $this->assertRegExp('#https://.*\.mercadopago\.com.+/checkout/#', $charge->authorization());
    }

    /** @test */
    public function it_should_fail_to_create_a_charge()
    {
        $order = $this->getOrderData();
        $payload = $order['payload'];
        $amount = $order['total'] / 2;

        $charge = $this->getPayMe()->charges()->create((int) ($amount), 'regular_payment', $payload);

        $this->assertFalse($charge->success());
        $this->assertTrue($charge->isRedirect());
    }
}
