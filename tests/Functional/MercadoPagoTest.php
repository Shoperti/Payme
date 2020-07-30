<?php

namespace Shoperti\Tests\PayMe\Functional;

use Shoperti\PayMe\Gateways\MercadoPago\Charges;
use Shoperti\PayMe\Gateways\MercadoPago\MercadoPagoGateway;

class MercadoPagoTest extends AbstractFunctionalTestCase
{
    protected $gatewayData = [
        'config'  => 'mercadopago',
        'gateway' => MercadoPagoGateway::class,
        'charges' => Charges::class,
    ];

    private function paymentTest($payload, $token, $success, $responseStatus = null, $responseType = null, $gateway = null)
    {
        /** @var \Shoperti\PayMe\PayMe $gateway */
        $gateway = $gateway ?: $this->getPayMe();

        $order = $this->getOrderData($payload ?: []);

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
            $this->getCredentials(),
            $cardNumber,
            $this->getOrderData()['payload']
        );

        $payload = [
            'card' => [
                'brand' => 'master',
            ],
        ];

        return $this->paymentTest($payload, $token, true, 'approved', 'credit_card');
    }

    /** @test */
    public function it_should_succeed_to_create_a_charge_with_ticket()
    {
        $payload = [
            'days_to_expire' => 3,
        ];

        $paymentResult = $this->paymentTest($payload, 'oxxo', true, 'pending', 'ticket');
        $data = $paymentResult[0];

        $this->assertArrayHasKey('transaction_details', $data);
        $this->assertArrayHasKey('external_resource_url', $data['transaction_details']);
        $this->assertNotNull($data['date_of_expiration']);

        return $paymentResult;
    }

    /** @test */
    public function it_should_succeed_to_create_a_charge_with_atm()
    {
        $data = $this->paymentTest(null, 'banamex', true, 'pending', 'atm')[0];

        $this->assertArrayHasKey('transaction_details', $data);
        $this->assertArrayHasKey('external_resource_url', $data['transaction_details']);
    }

    /** @test */
    public function it_should_fail_to_charge_a_token()
    {
        $payload = [
            'card' => [
                'brand' => 'master',
            ],
        ];

        $response = $this->paymentTest($payload, 'invalid_token', false)[2];

        $this->assertSame('Card Token not found', $response->message());
    }

    /** @test */
    public function it_should_fail_charging_with_invalid_access_key()
    {
        /** @var \Shoperti\PayMe\PayMe $gateway */
        $gateway = $this->getPayMe(['private_key' => 'invalid_key']);

        $payload = [
            'card' => [
                'brand' => 'master',
            ],
        ];

        $response = $this->paymentTest($payload, 'invalid_token', false, null, null, $gateway)[2];

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
        /** @var \Shoperti\PayMe\PayMe $payMe */
        $payMe = $this->getPayMe();

        list($response, $amount) = $responseAndAmount;

        /** @var \Shoperti\PayMe\Contracts\ResponseInterface $response */
        $response = $payMe->charges()->refund($amount, $response['id']);

        $data = $response->data();

        $this->assertTrue($response->success());

        $this->assertEquals($payMe->getGateway()->amount($amount), "{$data['amount']}");
    }

    /**
     * @test
     * @depends it_should_succeed_to_create_a_charge_with_ticket
     *
     * @param array $dataAndAmount
     */
    public function it_should_retrieve_a_single_event($dataAndAmount)
    {
        $chargeData = $dataAndAmount[0];

        /** @var \Shoperti\PayMe\Contracts\ResponseInterface $response */
        $response = $this->getPayMe()->events()->find($chargeData['id']);

        $data = $response->data();

        $this->assertTrue($response->success());
        $this->assertSame($chargeData['id'], $data['id']);
    }

    /** @test */
    public function it_should_retrieve_the_account_info()
    {
        /** @var \Shoperti\PayMe\Contracts\ResponseInterface $response */
        $response = $this->getPayMe()->account()->info();

        $data = $response->data();

        $this->assertTrue($response->success());
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('site_id', $data);
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
