<?php

namespace Shoperti\Tests\PayMe\Functional;

use Shoperti\PayMe\Gateways\PaypalPlus\PaypalPlusGateway;

class PaypalPlusTest extends AbstractFunctionalTestCase
{
    protected $gatewayData = [
        'config'     => 'paypal_plus',
        'gateway'    => PaypalPlusGateway::class,
        'isRedirect' => true,
    ];

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
        $response = $this->chargeRequest('request', null, ['token' => 'invalid-token']);

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
        $response = $this->successfulChargeRequest('request', null, [
            'token'        => $token,
            'continue_url' => 'https://myserver.com/show-form',
        ]);

        $data = $response->data();

        $this->assertSame('sale', $response->type());
        $this->assertSame($data['intent'], $response->type());
        $this->assertSame('pending', $response->status());
        $this->assertSame($data['id'], $response->reference());
        $this->assertStringStartsWith('https://myserver.com/show-form/?approval_url=https%3A%2F%2Fwww.sandbox.paypal.com%2Fcgi-bin%2Fwebscr%3Fcmd%3D_express-checkout%26token%3D', $response->authorization());

        $this->assertTrue($response->test());
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

    /**
     * @test
     * @depends it_should_generate_a_token
     *
     * @param string $token
     */
    public function it_should_get_all_hooks($token)
    {
        $gateway = $this->getPayMe();

        $tokenPayload = ['token' => $token];

        $webhooks = $gateway->webhooks()->all($tokenPayload);

        $this->assertTrue(is_array($webhooks));

        return $token;
    }

    /**
     * @test
     * @depends it_should_get_all_hooks
     */
    public function it_should_create_and_delete_a_webhook($token)
    {
        $gateway = $this->getPayMe();

        $tokenPayload = ['token' => $token];

        $webhooks = $gateway->webhooks()->all($tokenPayload);

        if (count($webhooks) > 5) {
            $deletable = $webhooks[count($webhooks) - 1];
            $gateway->webhooks()->delete($deletable['id']);
        }

        $url = 'https://httpbin.org/post?t='.time();

        // no permissions specified, all will be added
        $cratePayload = [
            'url'   => $url,
            'token' => $token,
        ];

        $created = $gateway->webhooks()->create($cratePayload)->data();

        $webhook = $gateway->webhooks()->find($created['id'], $tokenPayload);

        $gateway->webhooks()->delete($created['id'], $tokenPayload);

        $this->assertSame($created['url'], $webhook->data()['url']);
    }
}
