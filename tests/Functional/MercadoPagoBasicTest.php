<?php

namespace Shoperti\Tests\PayMe\Functional;

use Shoperti\PayMe\PayMe;

class MercadoPagoBasicTest extends AbstractFunctionalTestCase
{
    protected $gateway;

    public function setUp()
    {
        parent::setUp();

        $this->gateway = PayMe::make($this->credentials['mercadopago_basic']);
    }

    /** @test */
    public function it_should_create_a_new_mercadopago_basic_gateway()
    {
        $this->assertInstanceOf(\Shoperti\PayMe\Gateways\MercadoPagoBasic\MercadoPagoBasicGateway::class, $this->gateway->getGateway());
        $this->assertInstanceOf(\Shoperti\PayMe\Gateways\MercadoPagoBasic\Charges::class, $this->gateway->charges());
    }

    /** @test */
    public function it_should_succeed_to_create_a_charge()
    {
        $order = $this->getOrderPayload();
        $payload = $order['payload'];
        $amount = $order['total'];

        $charge = $this->gateway->charges()->create($amount, 'regular_payment', $payload);

        $this->assertFalse($charge->success());
        $this->assertTrue($charge->isRedirect());
        $this->assertContains('.mercadopago.com/mlm/checkout/', $charge->authorization());
    }

    /** @test */
    public function it_should_fail_to_create_a_charge()
    {
        $order = $this->getOrderPayload();
        $payload = $order['payload'];
        $amount = $order['total'] / 2;

        $charge = $this->gateway->charges()->create((int) ($amount), 'regular_payment', $payload);

        $this->assertFalse($charge->success());
        $this->assertTrue($charge->isRedirect());
    }
}
