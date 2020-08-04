<?php

namespace Shoperti\Tests\PayMe\Functional\Charges;

class PaypalExpressTest extends AbstractTest
{
    protected $gatewayData = [
        'config'     => 'paypal',
        'isRedirect' => true,
    ];

    /** @test */
    public function it_should_succeed_to_create_a_charge()
    {
        $charge = $this->successfulChargeRequest('SetExpressCheckout');

        $data = $charge->data();

        $this->assertEquals(null, $charge->type());
        $this->assertSame('pending', $charge->status());
        $this->assertSame($data['TOKEN'], $charge->reference());
        $this->assertRegExp('#EC-.{17}#', $charge->reference());
        $this->assertContains('https://www.sandbox.paypal.com/cgi-bin/webscr', $charge->authorization());
    }

    /** @test */
    public function it_should_fail_to_create_a_charge()
    {
        $orderData = $this->getOrderData();
        $charge = $this->getPayMe()->charges()->create($orderData['total'] - 100, 'SetExpressCheckout', $orderData['payload']);

        $this->assertFalse($charge->success());
        $this->assertTrue($charge->isRedirect());
    }
}
