<?php

namespace Shoperti\Tests\PayMe\Functional;

use Shoperti\PayMe\Gateways\Stripe\StripeGateway;

class StripeTest extends AbstractFunctionalTestCase
{
    protected $gatewayData = [
        'config'     => 'stripe',
        'gateway'    => StripeGateway::class,
    ];

    /** @test */
    public function is_should_succeed_to_charge_a_token()
    {
        $charge = $this->successfulChargeRequest($this->createToken(), 10000, null);

        $data = $charge->data();

        $this->assertEquals('charge', $charge->type());
        $this->assertEquals('paid', $charge->status());
        $this->assertEquals($data['id'], $charge->reference());
        $this->assertStringStartsWith('ch_', $charge->reference());
        $this->assertStringStartsWith('txn_', $charge->authorization());
    }

    /** @test */
    public function is_should_succeed_to_charge_a_token_with_params()
    {
        $charge = $this->successfulChargeRequest($this->createToken());

        $data = $charge->data();

        $this->assertEquals('charge', $charge->type());
        $this->assertEquals('paid', $charge->status());
        $this->assertEquals($data['id'], $charge->reference());
        $this->assertStringStartsWith('ch_', $charge->reference());
        $this->assertStringStartsWith('txn_', $charge->authorization());

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
    public function it_can_retrieve_a_single_and_all_events()
    {
        $events = $this->getPayMe()->events()->all();

        $this->assertNotEmpty($events[0]->data()['data']);
        $this->assertInternalType('array', $events[0]->data()['data']);

        $event = $this->getPayMe()->events()->find($events[0]->data()['id']);
    }

    protected function createToken(array $parameters = [])
    {
        $customer = $this->getPayMe()->customers()->create(array_merge([
            'email' => 'john@doe.com',
            'card'  => [
                'exp_month' => 10,
                'cvc'       => 314,
                'exp_year'  => 2020,
                'number'    => '4242424242424242',
            ],
        ], $parameters))->data();

        return $customer['id'];
    }
}
