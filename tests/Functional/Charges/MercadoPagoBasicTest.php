<?php

namespace Shoperti\Tests\PayMe\Functional\Charges;

class MercadoPagoBasicTest extends AbstractTest
{
    protected $gatewayData = [
        'config'     => 'mercadopago_basic',
        'isRedirect' => true,
    ];

    /** @test */
    public function it_should_succeed_to_create_a_charge()
    {
        $charge = $this->successfulChargeRequest('regular_payment');

        $data = $charge->data();

        $this->assertEquals(null, $charge->type());
        $this->assertEquals('pending', $charge->status());
        $this->assertEquals($data['id'], $charge->reference());
        $this->assertRegExp('#https://.*\.mercadopago\.com.+/checkout/#', $charge->authorization());
    }

    /** @test */
    public function it_should_fail_to_create_a_charge()
    {
        $charge = $this->chargeRequest('regular_payment', (int) $this->getOrderData()['total'] / 2);

        $this->assertFalse($charge->success());
        $this->assertTrue($charge->isRedirect());
    }
}
