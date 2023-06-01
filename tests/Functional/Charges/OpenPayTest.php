<?php

namespace Shoperti\Tests\PayMe\Functional\Charges;

class OpenPayTest extends AbstractTest
{
    protected $gatewayData = [
        'config' => 'open_pay',
    ];

    /** @test */
    public function it_should_fail_to_charge_a_token()
    {
        /** @var \Shoperti\PayMe\Contracts\ResponseInterface $response */
        $response = $this->getPayMe()->charges()->create(1000, 'tok_test_card_declined');

        $this->assertFalse($response->success());
    }

    /** @test */
    public function it_should_fail_charging_with_invalid_access_key()
    {
        /** @var \Shoperti\PayMe\PayMe $payMe */
        $payMe = $this->getPayMe(['private_key' => 'invalid_key']);

        /** @var \Shoperti\PayMe\Contracts\ResponseInterface $response */
        $response = $payMe->charges()->create(1000, 'tok_test_card_declined');

        $this->assertSame('The api key or merchant id are invalid', $response->message());
    }

    /** @test */
    public function it_should_succeed_to_generate_a_store_payment()
    {
        $response = $this->successfulChargeRequest('store');

        $data = $response->data();
        ['total' => $amount, 'payload' => $payload] = $this->getOrderData();

        $this->assertSame('charge', $response->type());
        $this->assertSame('pending', $response->status());
        $this->assertSame($data['id'], $response->reference());
        $this->assertRegExp('#https://(sandbox-)?api.openpay.mx/barcode/.+#', $response->authorization());

        $this->assertEquals($this->getPayMe()->getGateway()->amount($amount), "{$data['amount']}");
        $this->assertSame($payload['first_name'], $data['customer']['name']);
        $this->assertSame($payload['last_name'], $data['customer']['last_name']);
        $this->assertSame($payload['currency'], $data['currency']);
    }

    /** @test */
    public function it_should_succeed_to_generate_a_bank_transfer()
    {
        $response = $this->successfulChargeRequest('bank_account');

        $data = $response->data();
        ['total' => $amount, 'payload' => $payload] = $this->getOrderData();

        $this->assertSame('charge', $response->type());
        $this->assertSame('pending', $response->status());
        $this->assertSame($data['id'], $response->reference());
        $this->assertRegExp('#\d{18}#', $response->authorization());

        $this->assertEquals($this->getPayMe()->getGateway()->amount($amount), "{$data['amount']}");
        $this->assertSame('bank_account', $data['method']);
        $this->assertSame('in_progress', $data['status']);
        $this->assertSame($payload['first_name'], $data['customer']['name']);
        $this->assertSame($payload['last_name'], $data['customer']['last_name']);
        $this->assertSame($payload['currency'], $data['currency']);
    }

    /** @test */
    public function it_should_succeed_to_charge_a_token_with_params()
    {
        ['total' => $amount, 'payload' => $payload] = $this->getOrderData();

        $response = $this->successfulChargeRequest($this->getToken($this->getCredentials(), '4242424242424242', $payload));

        $data = $response->data();

        $this->assertSame('charge', $response->type());
        $this->assertSame('paid', $response->status());
        $this->assertSame($data['id'], $response->reference());
        $this->assertSame($data['authorization'], $response->authorization());

        $this->assertEquals($this->getPayMe()->getGateway()->amount($amount), "{$data['amount']}");
        $this->assertSame('card', $data['method']);
        $this->assertSame('completed', $data['status']);
        $this->assertSame($payload['first_name'], $data['customer']['name']);
        $this->assertSame($payload['last_name'], $data['customer']['last_name']);
        $this->assertSame($payload['currency'], $data['currency']);

        return [$data, $amount];
    }

    /**
     * @test
     *
     * @depends it_should_succeed_to_charge_a_token_with_params
     *
     * @param array $dataAndAmount
     */
    public function it_should_retrieve_a_single_event($dataAndAmount)
    {
        $chargeData = $dataAndAmount[0];
        $options = ['event' => 'charge.succeeded'];

        /** @var \Shoperti\PayMe\Contracts\ResponseInterface $response */
        $response = $this->getPayMe()->events()->find($chargeData['id'], $options);

        $data = $response->data();

        $this->assertTrue($response->success());
        $this->assertSame($chargeData['id'], $data['id']);
    }

    /**
     * @test
     *
     * @depends it_should_succeed_to_charge_a_token_with_params
     *
     * @param array $responseAndAmount
     */
    public function it_should_succeed_to_refund_a_charge($responseAndAmount)
    {
        /** @var \Shoperti\PayMe\PayMe $payMe */
        $payMe = $this->getPayMe();

        [$response, $amount] = $responseAndAmount;

        /** @var \Shoperti\PayMe\Contracts\ResponseInterface $response */
        $response = $payMe->charges()->refund($amount, $response['id']);

        $data = $response->data();

        $this->assertTrue($response->success());
        $this->assertSame('refund', $response->type());
        $this->assertSame($response['refund']['id'], $response->reference());
        $this->assertSame($response['refund']['authorization'], $response->authorization());
        $this->assertEquals($payMe->getGateway()->amount($amount), "{$data['refund']['amount']}");
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
        $response = (new \GuzzleHttp\Client())->post("https://sandbox-api.openpay.mx/v1/{$credentials['id']}/tokens", [
            'headers' => [
                'Authorization' => 'Basic '.base64_encode($credentials['private_key'].':'),
                'Content-Type'  => 'application/json',
                'User-Agent'    => 'OpenPay PayMeBindings',
            ],
            'json' => [
                'card_number'      => $cardNumber,
                'holder_name'      => $payload['name'],
                'expiration_year'  => '40',
                'expiration_month' => '12',
                'cvv2'             => '123',
                'method'           => 'token',
                'address'          => [
                    'city'         => $payload['billing_address']['city'],
                    'country_code' => $payload['billing_address']['country'],
                    'postal_code'  => $payload['billing_address']['zip'],
                    'line1'        => $payload['billing_address']['address1'],
                    'line2'        => $payload['billing_address']['address2'],
                    'line3'        => '',
                    'state'        => $payload['billing_address']['state'],
                ],
            ],
        ]);

        $response = json_decode($response->getBody(), true);

        return $response['id'];
    }
}
