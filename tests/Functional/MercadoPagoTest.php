<?php

namespace Shoperti\Tests\PayMe\Functional;

use Shoperti\PayMe\PayMe;

class MercadoPagoTest extends AbstractFunctionalTestCase
{
    /** @test */
    public function it_should_create_a_new_mercadopago_gateway()
    {
        $gateway = PayMe::make($this->credentials['mercadopago']);

        $this->assertInstanceOf('Shoperti\PayMe\Gateways\MercadoPago\MercadoPagoGateway', $gateway->getGateway());
        $this->assertInstanceOf('Shoperti\PayMe\Gateways\MercadoPago\Charges', $gateway->charges());
    }

    /** @test */
    public function it_should_succeed_to_charge_a_token_with_params()
    {
        /** @var \Shoperti\PayMe\PayMe $gateway */
        $gateway = PayMe::make($this->credentials['mercadopago']);

        $cardNumber = '5031755734530604';

        $amount = 1234;

        $payload = $this->getPayload();

        $token = $this->getToken($this->credentials['mercadopago'], $cardNumber, $payload);

        /** @var \Shoperti\PayMe\Contracts\ResponseInterface $response */
        $response = $gateway->charges()->create($amount, $token, $payload);

        $data = $response->data(); 

        $this->assertTrue($response->success());
        $this->assertSame($gateway->getGateway()->amount($amount), "{$data['transaction_details']['total_paid_amount']}");
        $this->assertSame('regular_payment', $data['operation_type']);
        $this->assertSame('approved', $data['status']);
        $this->assertSame($payload['first_name'], $data['additional_info']['payer']['first_name']);
        $this->assertSame($payload['last_name'], $data['additional_info']['payer']['last_name']);

        return [$data, $amount];
    }

    /** @test */
    public function it_should_fail_to_charge_a_token()
    {
        /** @var \Shoperti\PayMe\PayMe $gateway */
        $gateway = PayMe::make($this->credentials['mercadopago']);

        /** @var \Shoperti\PayMe\Contracts\ResponseInterface $response */
        $response = $gateway->charges()->create(1000, 'tok_test_card_declined');

        $this->assertFalse($response->success());
    }

    /** @test */
    public function it_should_fail_charging_with_invalid_access_key()
    {
        /** @var \Shoperti\PayMe\PayMe $gateway */
        $gateway = PayMe::make(array_merge($this->credentials['mercadopago'], ['private_key' => 'invalid_key']));

        /** @var \Shoperti\PayMe\Contracts\ResponseInterface $response */
        $response = $gateway->charges()->create(1000, 'tok_test_card_declined');

        $this->assertSame('Malformed access_token: null', $response->message());
    }

    /**
     * @test
     * @depends it_should_succeed_to_charge_a_token_with_params
     *
     * @param array $responseAndAmount
     */
    public function it_should_succeed_to_refund_a_charge($responseAndAmount)
    {
        /** @var \Shoperti\PayMe\PayMe $openPayPayMe */
        $openPayPayMe = PayMe::make($this->credentials['mercadopago']);

        list($response, $amount) = $responseAndAmount;

        /** @var \Shoperti\PayMe\Contracts\ResponseInterface $response */
        $response = $openPayPayMe->charges()->refund($amount, $response['id']);

        $data = $response->data();

        $this->assertTrue($response->success());
        // previous test creates a charge of 1000
        $this->assertSame($openPayPayMe->getGateway()->amount($amount), "{$data['amount']}");
    }

    /**
     * @test
     * @depends it_should_succeed_to_charge_a_token_with_params
     *
     * @param array $dataAndAmount
     */
    public function it_should_retrieve_a_single_event($dataAndAmount)
    {
        /** @var \Shoperti\PayMe\PayMe $gateway */
        $gateway = PayMe::make($this->credentials['mercadopago']);

        $chargeData = $dataAndAmount[0];
        $options = ['type' => 'payment'];

        /** @var \Shoperti\PayMe\Contracts\ResponseInterface $response */
        $response = $gateway->events()->find($chargeData['id'], $options);

        $data = $response->data();

        $this->assertTrue($response->success());
        $this->assertSame($chargeData['id'], $data['id']);
    }

    /**
     * Gets a request payload array.
     *
     * @return array
     */
    private function getPayload()
    {
        return [
            'reference'        => 'order_'.time().rand(10000, 99999),
            'device_id'        => 'test',
            'description'      => 'Awesome Store',
            'currency'         => 'MXN',
            'return_url'       => 'http://google.com',
            'notify_url'       => 'http://google.com',
            'cancel_url'       => 'http://google.com',
            'name'             => 'Juan Pérez',
            'first_name'       => 'Juan',
            'last_name'        => 'Pérez',
            'email'            => 'test_user_75095492@testuser.com',
            'phone'            => '525555555',
            'discount'         => 0,
            'discount_concept' => null,
            'card'             => [
                'brand' => 'master',
            ],
            'line_items'       => [
                [
                    'name'        => 'Zapato dama modelo TOLDA 24 Negro',
                    'description' => 'Zapato dama modelo TOLDA 24 Negro',
                    'unit_price'  => 14000,
                    'quantity'    => 2,
                    'sku'         => 'TOLDAA',
                ],
            ],
            'billing_address' => [
                'address1' => 'calle falsa 123',
                'address2' => 'Colonia',
                'city'     => 'delegación',
                'country'  => 'MX',
                'state'    => 'Ciudad de México',
                'zip'      => '12345',
            ],
            'shipping_address' => [
                'address1' => 'calle falsa 123',
                'address2' => 'Colonia',
                'city'     => 'delegación',
                'country'  => 'MX',
                'state'    => 'Ciudad de México',
                'zip'      => '12345',
                'price'    => 10000,
                'carrier'  => 'shoperti',
                'service'  => 'pending',
            ],
        ];
    }

    /**
     * Generate a token for tests.
     *
     * @param array  $credentials
     * @param string $cardNumber
     * @param array  $payload
     *
     * @return string
     */
    private function getToken($credentials, $cardNumber, $payload)
    {
        $response = (new \GuzzleHttp\Client())->post("https://api.mercadopago.com/v1/card_tokens?access_token={$credentials['private_key']}", [
            'headers' => [
                'Content-Type'  => 'application/json',
                'User-Agent'    => 'MercadoPago PayMeBindings',
            ],
            'json' => [
                'card_number'      => $cardNumber,
                'holder_name'      => $payload['name'],
                'expiration_year'  => 2022,
                'expiration_month' => 2,
                'security_code'    => '123',
                'cardholder'       => [
                    'name' => $payload['name'],
                ],
                'currency_id'      => 'USD',
            ],
        ]);

        $response = json_decode($response->getBody(), true);

        return $response['id'];
    }
}
