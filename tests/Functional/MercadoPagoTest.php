<?php

namespace Shoperti\Tests\PayMe\Functional;

use Shoperti\PayMe\PayMe;

class MercadoPagoTest extends AbstractFunctionalTestCase
{
    /** @test */
    public function it_should_create_a_new_mercadopago_gateway()
    {
        $gateway = PayMe::make($this->credentials['mercadopago']);

        $this->assertInstanceOf(\Shoperti\PayMe\Gateways\MercadoPago\MercadoPagoGateway::class, $gateway->getGateway());
        $this->assertInstanceOf(\Shoperti\PayMe\Gateways\MercadoPago\Charges::class, $gateway->charges());
    }

    private function paymentTest($methodId, $token, $success, $responseStatus = null, $responseType = null, $gateway = null)
    {
        /** @var \Shoperti\PayMe\PayMe $gateway */
        $gateway = $gateway ?: PayMe::make($this->credentials['mercadopago']);

        $order = $this->getOrderPayload([
            'card' => [
                'brand' => $methodId,
            ],
        ]);

        $payload = $order['payload'];
        $amount = $order['total'];

        /** @var \Shoperti\PayMe\Contracts\ResponseInterface $response */
        $response = $gateway->charges()->create($amount, $token, $payload);

        $data = $response->data();

        $this->assertSame($success, $response->success());

        if ($success) {
            $this->assertEquals($gateway->getGateway()->amount($amount), "{$data['transaction_details']['total_paid_amount']}");
            $this->assertSame('regular_payment', $data['operation_type']);
            $this->assertSame($responseStatus, $data['status']);
            $this->assertSame($responseType, $data['payment_type_id']);
            $this->assertSame($payload['first_name'], $data['additional_info']['payer']['first_name']);
            $this->assertSame($payload['last_name'], $data['additional_info']['payer']['last_name']);
        }

        return [$data, $amount, $response];
    }

    /** @test */
    public function it_should_succeed_to_charge_a_token_with_params()
    {
        $cardNumber = '5031755734530604';

        $token = $this->getToken(
            $this->credentials['mercadopago'],
            $cardNumber,
            $this->getOrderPayload()['payload']
        );

        return $this->paymentTest('master', $token, true, 'approved', 'credit_card');
    }

    /** @test */
    public function it_should_succeed_to_create_a_charge_with_atm()
    {
        $data = $this->paymentTest('banamex', null, true, 'pending', 'atm')[0];

        $this->assertArrayHasKey('transaction_details', $data);
        $this->assertArrayHasKey('external_resource_url', $data['transaction_details']);
    }

    /** @test */
    public function it_should_succeed_to_create_a_charge_with_ticket()
    {
        $data = $this->paymentTest('oxxo', null, true, 'pending', 'ticket')[0];

        $this->assertArrayHasKey('transaction_details', $data);
        $this->assertArrayHasKey('external_resource_url', $data['transaction_details']);
    }

    /** @test */
    public function it_should_fail_to_charge_a_token()
    {
        $response = $this->paymentTest('master', 'invalid_token', false)[2];

        $this->assertSame('Card Token not found', $response->message());
    }

    /** @test */
    public function it_should_fail_charging_with_invalid_access_key()
    {
        /** @var \Shoperti\PayMe\PayMe $gateway */
        $gateway = PayMe::make(array_merge($this->credentials['mercadopago'], ['private_key' => 'invalid_key']));

        $response = $this->paymentTest('master', 'invalid_token', false, null, null, $gateway)[2];

        $this->assertSame('Malformed access_token: invalid_key', $response->message());
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

        $this->assertEquals($openPayPayMe->getGateway()->amount($amount), "{$data['amount']}");
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
                'expiration_year'  => 2022,
                'expiration_month' => 2,
                'security_code'    => '123',
                'cardholder'       => [
                    'name' => $payload['name'],
                ],
            ],
        ]);

        $response = json_decode($response->getBody(), true);

        return $response['id'];
    }
}
