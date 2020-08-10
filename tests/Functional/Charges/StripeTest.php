<?php

namespace Shoperti\Tests\PayMe\Functional\Charges;

class StripeTest extends AbstractTest
{
    protected $gatewayData = [
        'config'     => 'stripe',
        'isRedirect' => true,
    ];

    /** @test */
    public function is_should_succeed_to_charge_a_token()
    {
        $charge = $this->successfulChargeRequest(null, 10000, null);

        $data = $charge->data();

        $this->assertEquals('payment_intent', $charge->type());
        $this->assertEquals('pending', $charge->status());
        $this->assertEquals($data['id'], $charge->reference());
        $this->assertStringStartsWith('pi_', $charge->reference());
        $this->assertStringStartsWith('?reference=pi_', $charge->authorization());
    }

    /** @test */
    public function is_should_succeed_to_charge_a_token_with_params()
    {
        $charge = $this->successfulChargeRequest(null);

        $data = $charge->data();

        $this->assertEquals('payment_intent', $charge->type());
        $this->assertEquals('pending', $charge->status());
        $this->assertEquals($data['id'], $charge->reference());
        $this->assertStringStartsWith('pi_', $charge->reference());
        $this->assertStringStartsWith('?reference=pi_', $charge->authorization());

        $this->assertStringStartsWith('payme_order_', $data['metadata']['reference']);
    }

    /** @test */
    public function it_should_fail_with_invalid_access_key()
    {
        $gateway = $this->getPayMe(['private_key' => 'invalid_key']);

        $charge = $gateway->charges()->create(1000, null);

        $this->assertSame($charge->message(), 'Invalid API Key provided: invalid_key');
    }

    /** @test */
    public function it_should_fail_with_invalid_amount()
    {
        $gateway = $this->getPayMe();

        $charge = $gateway->charges()->create(1, null);

        $this->assertFalse($charge->success());
        $this->assertNull($charge->type());
        $this->assertEquals('declined', $charge->status());
        $this->assertEquals('invalid_amount', $charge->errorCode());
    }

    /** @test */
    public function it_should_fail_with_invalid_payload()
    {
        $gateway = $this->getPayMe();

        $charge = $gateway->charges()->create(null, ['currency' => null]);

        $this->assertFalse($charge->success());
        $this->assertNull($charge->type());
        $this->assertEquals('declined', $charge->status());
        $this->assertEquals('config_error', $charge->errorCode());
    }
}
