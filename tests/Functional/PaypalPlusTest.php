<?php

namespace Shoperti\Tests\PayMe\Functional;

class PaypalPlusTest extends AbstractFunctionalTestCase
{
    protected $gatewayKey = 'paypal_plus';

    /** @test */
    public function it_should_create_a_new_gateway()
    {
        $gateway = $this->getPayMe();

        $this->assertInstanceOf(\Shoperti\PayMe\Gateways\PaypalPlus\PaypalPlusGateway::class, $gateway->getGateway());
        $this->assertInstanceOf(\Shoperti\PayMe\Gateways\PaypalPlus\Charges::class, $gateway->charges());
    }

    /** @test */
    public function it_should_fail_to_generate_a_token()
    {
        $token = $this->getPayMe(['client_secret' => 'invalid_key'])->getGateway()->token();

        $this->assertNull($token['token']);
        $this->assertNull($token['type']);
        $this->assertNull($token['scope']);
        $this->assertNull($token['expiry']);
    }

    /** @test */
    public function it_should_fail_charging_with_invalid_token()
    {
        $gateway = $this->getPayMe();

        $order = $this->getOrderPayload();

        $amount = $order['total'];
        $payload = $order['payload'];
        $payload['token'] = 'invalid-token';

        /** @var \Shoperti\PayMe\Contracts\ResponseInterface $response */
        $response = $gateway->charges()->create($amount, $payload);

        $this->assertFalse($response->success());
        $this->assertSame('failed', (string) $response->status());
    }

    /** @test */
    public function it_should_generate_a_token()
    {
        $token = $this->getPayMe()->getGateway()->token();

        $this->assertNotEmpty($token['token']);
        $this->assertSame('Bearer', $token['type']);
        $this->assertNotEmpty($token['scope']);
        $this->assertInternalType('int', $token['expiry']);

        return $token['token'];
    }

    /**
     * @test
     * @depends it_should_generate_a_token
     *
     * @param string $token
     */
    public function it_should_succeed_to_request_a_charge($token)
    {
        $gateway = $this->getPayMe();

        $order = $this->getOrderPayload();

        $amount = $order['total'];
        $payload = $order['payload'];
        $payload['token'] = $token;
        $payload['continue_url'] = 'https://myserver.com/show-form';

        /** @var \Shoperti\PayMe\Contracts\ResponseInterface $response */
        $response = $gateway->charges()->create($amount, 'request', $payload);

        $data = $response->data();

        $this->assertTrue($response->success());
        $this->assertSame($data['id'], $response->reference());
        $this->assertTrue($response->test());
        $this->assertStringStartsWith('https://myserver.com/show-form/?approval_url=https%3A%2F%2Fwww.sandbox.paypal.com%2Fcgi-bin%2Fwebscr%3Fcmd%3D_express-checkout%26token%3D', $response->authorization());
        $this->assertSame('pending', (string) $response->status());
        $this->assertEmpty($response->errorCode());
        $this->assertSame($data['intent'], $response->type());
    }

    /**
     * @test
     * @depends it_should_generate_a_token
     *
     * @param string $token
     */
    public function it_should_validate_event($token)
    {
        $gateway = $this->getPayMe();

        $payload = [
            'payment_type'         => 'instant',
            'payment_date'         => 'Thu Sep 28 2017 22:16:01 GMT-0500 (CDT)',
            'payment_status'       => 'Completed',
            'address_status'       => 'confirmed',
            'payer_status'         => 'verified',
            'first_name'           => 'John',
            'last_name'            => 'Smith',
            'payer_email'          => 'buyer@paypalsandbox.com',
            'payer_id'             => 'TESTBUYERID01',
            'address_name'         => 'John Smith',
            'address_country'      => 'United States',
            'address_country_code' => 'US',
            'address_zip'          => '95131',
            'address_state'        => 'CA',
            'address_city'         => 'San Jose',
            'address_street'       => '123 any street',
            'business'             => 'seller@paypalsandbox.com',
            'receiver_email'       => 'seller@paypalsandbox.com',
            'receiver_id'          => 'seller@paypalsandbox.com',
            'residence_country'    => 'US',
            'item_name1'           => 'something',
            'item_number1'         => 'AK-1234',
            'tax'                  => '2.02',
            'mc_currency'          => 'USD',
            'mc_fee'               => '0.44',
            'mc_gross'             => '12.34',
            'mc_gross_1'           => '12.34',
            'mc_handling'          => '2.06',
            'mc_handling1'         => '1.67',
            'mc_shipping'          => '3.02',
            'mc_shipping1'         => '1.02',
            'txn_type'             => 'cart',
            'txn_id'               => '195047926',
            'notify_version'       => '2.1',
            'custom'               => 'xyz123',
            'invoice'              => 'abc1234',
            'test_ipn'             => '1',
            'verify_sign'          => 'AFcWxV21C7fd0v3bYYYRCpSSRl31AT-k5RWJQQ1ltu6rslsRc91idKIv',
        ];

        $response = $gateway->events()->find('txn_id', $payload);

        $this->assertTrue($response->success());
        $this->assertSame($payload['invoice'], $response->reference());
        $this->assertSame('VERIFIED', $response->message());
        $this->assertTrue($response->test());
        $this->assertSame($payload['txn_id'], $response->authorization());
        $this->assertSame('paid', (string) $response->status());
        $this->assertEmpty($response->errorCode());
        $this->assertSame($payload['txn_type'], $response->type());
    }
}
