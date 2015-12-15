<?php

namespace Shoperti\Tests\PayMe\Functional;

use Shoperti\PayMe\PayMe;

class StripeTest extends AbstractFunctionalTestCase
{
    protected $gateway;

    public function setUp()
    {
        parent::setUp();

        $this->gateway = PayMe::make($this->credentials['stripe']);
    }

    /** @test */
    public function it_should_create_a_new_conekta_gateway()
    {
        $this->assertInstanceOf('Shoperti\PayMe\Gateways\Stripe\StripeGateway', $this->gateway->getGateway());
        $this->assertInstanceOf('Shoperti\PayMe\Gateways\Stripe\Charges', $this->gateway->charges());
    }

    /** @test */
    public function is_should_succeed_to_charge_a_token()
    {
        $token = $this->createToken();

        $charge = $this->gateway->charges()->create(1000, $token);

        $this->assertTrue($charge->success());
    }

    /** @test */
    public function is_should_fail_to_charge_a_token()
    {
        $charge = $this->gateway->charges()->create(1000, 'tok_test_card_declined');

        $this->assertFalse($charge->success());
    }

    /** @test */
    public function is_should_succeed_to_charge_a_token_with_params()
    {
        $token = $this->createToken();

        $charge = $this->gateway->charges()->create(1000, $token, [
            'reference' => 'order_1',
        ]);

        $response = $charge->data();

        $this->assertTrue($charge->success());
        $this->assertSame($response['metadata']['reference'], 'order_1');
    }

    /** @test */
    public function it_sould_fail_with_invalid_access_key()
    {
        $gateway = PayMe::make(array_merge($this->credentials['stripe'], ['private_key' => 'invalid_key']));

        $charge = $gateway->charges()->create(1000, 'tok_test_card_declined');

        $this->assertSame($charge->message(), 'Invalid API Key provided: invalid_key');
    }

    protected function createToken(array $parameters = [])
    {
        $customer = $this->gateway->customers()->create(array_merge([
            'email' => 'john@doe.com',
            'card' => [
                'exp_month' => 10,
                'cvc'       => 314,
                'exp_year'  => 2020,
                'number'    => '4242424242424242',
            ],
        ], $parameters))->data();

        return $customer['id'];
    }
}
