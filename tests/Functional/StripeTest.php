<?php

namespace Shoperti\Tests\PayMe\Functional;

use Shoperti\PayMe\Gateways\Stripe\Charges;
use Shoperti\PayMe\Gateways\Stripe\StripeGateway;

class StripeTest extends AbstractFunctionalTestCase
{
    protected $gatewayData = [
        'config'  => 'stripe',
        'gateway' => StripeGateway::class,
        'charges' => Charges::class,
    ];

    /** @test */
    public function is_should_succeed_to_charge_a_token()
    {
        $token = $this->createToken();

        $charge = $this->getPayMe()->charges()->create(1000, $token);

        $this->assertTrue($charge->success());
    }

    /** @test */
    public function is_should_fail_to_charge_a_token()
    {
        $charge = $this->getPayMe()->charges()->create(1000, 'tok_test_card_declined');

        $this->assertFalse($charge->success());
    }

    /** @test */
    public function is_should_succeed_to_charge_a_token_with_params()
    {
        $token = $this->createToken();

        $charge = $this->getPayMe()->charges()->create(1000, $token, [
            'reference' => 'order_1',
        ]);

        $response = $charge->data();

        $this->assertTrue($charge->success());
        $this->assertSame($response['metadata']['reference'], 'order_1');
    }

    /** @test */
    public function it_sould_fail_with_invalid_access_key()
    {
        $gateway = $this->getPayMe(['private_key' => 'invalid_key']);

        $charge = $gateway->charges()->create(1000, 'tok_test_card_declined');

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
