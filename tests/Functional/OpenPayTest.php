<?php

namespace Shoperti\Tests\PayMe\Functional;

use Shoperti\PayMe\PayMe;

class OpenPayTest extends AbstractFunctionalTestCase
{
    /** @test */
    public function it_should_create_a_new_openpay_gateway()
    {
        /** @var \Shoperti\PayMe\PayMe $openPayPayMe */
        $openPayPayMe = PayMe::make($this->credentials['open_pay']);

        $this->assertInstanceOf('Shoperti\PayMe\Gateways\OpenPay\OpenPayGateway', $openPayPayMe->getGateway());
        $this->assertInstanceOf('Shoperti\PayMe\Gateways\OpenPay\Charges', $openPayPayMe->charges());
    }

    /** @test */
    public function it_should_fail_to_charge_a_token()
    {
        /** @var \Shoperti\PayMe\PayMe $openPayPayMe */
        $openPayPayMe = PayMe::make($this->credentials['open_pay']);

        /** @var \Shoperti\PayMe\Contracts\ResponseInterface $response */
        $response = $openPayPayMe->charges()->create(1000, 'tok_test_card_declined');

        $this->assertFalse($response->success());
    }

    /** @test */
    public function it_should_fail_charging_with_invalid_access_key()
    {
        /** @var \Shoperti\PayMe\PayMe $openPayPayMe */
        $openPayPayMe = PayMe::make(array_merge($this->credentials['open_pay'], ['private_key' => 'invalid_key']));

        /** @var \Shoperti\PayMe\Contracts\ResponseInterface $response */
        $response = $openPayPayMe->charges()->create(1000, 'tok_test_card_declined');

        $this->assertSame('The api key or merchant id are invalid', $response->message());
    }

    /** @test */
    public function it_should_succeed_to_generate_a_store_payment()
    {
        /** @var \Shoperti\PayMe\PayMe $openPayPayMe */
        $openPayPayMe = PayMe::make($this->credentials['open_pay']);

        $order = $this->getOrderPayload();
        $amount = $order['total'];
        $payload = $order['payload'];

        $method = 'store';

        /** @var \Shoperti\PayMe\Contracts\ResponseInterface $response */
        $response = $openPayPayMe->charges()->create($amount, $method, $payload);

        $data = $response->data();

        $this->assertTrue($response->success());
        $this->assertEquals($openPayPayMe->getGateway()->amount($amount), "{$data['amount']}");
        $this->assertSame('store', $data['method']);
        $this->assertSame('in_progress', $data['status']);
        $this->assertSame('charge', $response->type());
        $this->assertSame($data['id'], $response->reference());
        $this->assertSame($payload['first_name'], $data['customer']['name']);
        $this->assertSame($payload['last_name'], $data['customer']['last_name']);
        $this->assertSame($payload['currency'], $data['currency']);
    }

    /** @test */
    public function it_should_succeed_to_generate_a_bank_transfer()
    {
        /** @var \Shoperti\PayMe\PayMe $openPayPayMe */
        $openPayPayMe = PayMe::make($this->credentials['open_pay']);

        $order = $this->getOrderPayload();
        $amount = $order['total'];
        $payload = $order['payload'];

        $method = 'bank_account';

        /** @var \Shoperti\PayMe\Contracts\ResponseInterface $response */
        $response = $openPayPayMe->charges()->create($amount, $method, $payload);

        $data = $response->data();

        $this->assertTrue($response->success());
        $this->assertEquals($openPayPayMe->getGateway()->amount($amount), "{$data['amount']}");
        $this->assertSame('bank_account', $data['method']);
        $this->assertSame('in_progress', $data['status']);
        $this->assertSame('charge', $response->type());
        $this->assertSame($data['id'], $response->reference());
        $this->assertSame($payload['first_name'], $data['customer']['name']);
        $this->assertSame($payload['last_name'], $data['customer']['last_name']);
        $this->assertSame($payload['currency'], $data['currency']);
    }

    /** @test */
    public function it_should_succeed_to_charge_a_token_with_params()
    {
        /** @var \Shoperti\PayMe\PayMe $openPayPayMe */
        $openPayPayMe = PayMe::make($this->credentials['open_pay']);

        $cardNumber = '4242424242424242';

        $order = $this->getOrderPayload();
        $amount = $order['total'];
        $payload = $order['payload'];

        $token = $this->getToken($this->credentials['open_pay'], $cardNumber, $payload);

        /** @var \Shoperti\PayMe\Contracts\ResponseInterface $response */
        $response = $openPayPayMe->charges()->create($amount, $token, $payload);

        $data = $response->data();

        $this->assertTrue($response->success());
        $this->assertEquals($openPayPayMe->getGateway()->amount($amount), "{$data['amount']}");
        $this->assertSame('card', $data['method']);
        $this->assertSame('completed', $data['status']);
        $this->assertSame('charge', $response->type());
        $this->assertSame($data['id'], $response->reference());
        $this->assertSame($data['authorization'], $response->authorization());
        $this->assertSame($payload['first_name'], $data['customer']['name']);
        $this->assertSame($payload['last_name'], $data['customer']['last_name']);
        $this->assertSame($payload['currency'], $data['currency']);

        return [$data, $amount];
    }

    /**
     * @test
     * @depends it_should_succeed_to_charge_a_token_with_params
     *
     * @param array $dataAndAmount
     */
    public function it_should_retrieve_a_single_event($dataAndAmount)
    {
        /** @var \Shoperti\PayMe\PayMe $openPayPayMe */
        $openPayPayMe = PayMe::make($this->credentials['open_pay']);

        $chargeData = $dataAndAmount[0];
        $options = ['event' => 'charge.succeeded'];

        /** @var \Shoperti\PayMe\Contracts\ResponseInterface $response */
        $response = $openPayPayMe->events()->find($chargeData['id'], $options);

        $data = $response->data();

        $this->assertTrue($response->success());
        $this->assertSame($chargeData['id'], $data['id']);
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
        $openPayPayMe = PayMe::make($this->credentials['open_pay']);

        list($response, $amount) = $responseAndAmount;

        /** @var \Shoperti\PayMe\Contracts\ResponseInterface $response */
        $response = $openPayPayMe->charges()->refund($amount, $response['id']);

        $data = $response->data();

        $this->assertTrue($response->success());
        $this->assertSame('refund', $response->type());
        $this->assertSame($response['refund']['id'], $response->reference());
        $this->assertSame($response['refund']['authorization'], $response->authorization());
        $this->assertEquals($openPayPayMe->getGateway()->amount($amount), "{$data['refund']['amount']}");
    }

    /** @test */
    public function it_should_create_get_and_delete_a_webhook()
    {
        $url = 'https://httpbin.org/post';

        $gateway = PayMe::make($this->credentials['open_pay']);

        $payload = [
            'url'         => $url,
            'event_types' => [
                'charge.refunded',
                'charge.failed',
                'charge.cancelled',
                'charge.created',
                'charge.succeeded',
                'charge.rescored.to.decline',
                'subscription.charge.failed',
                'payout.created',
                'payout.succeeded',
                'payout.failed',
                'transfer.succeeded',
                'fee.succeeded',
                'fee.refund.succeeded',
                'spei.received',
                'chargeback.created',
                'chargeback.rejected',
                'chargeback.accepted',
                'order.created',
                'order.activated',
                'order.payment.received',
                'order.completed',
                'order.expired',
                'order.cancelled',
                'order.payment.cancelled',
            ],
        ];

        /** @var \Shoperti\PayMe\Contracts\WebhookInterface $openPayHooks */
        $openPayHooks = $gateway->webhooks();

        /** @var \Shoperti\PayMe\Contracts\ResponseInterface $response */
        $response = $openPayHooks->create($payload);

        $data = $response->data();

        $webhook = $openPayHooks->find($data['id']);

        $openPayHooks->delete($data['id']);

        $this->assertSame($data['url'], $webhook->data()['url']);
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
                'expiration_year'  => '20',
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
