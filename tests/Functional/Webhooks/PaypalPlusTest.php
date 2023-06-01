<?php

namespace Shoperti\Tests\PayMe\Functional\Webhooks;

class PaypalPlusTest extends AbstractTest
{
    protected $gatewayData = [
        'config'     => 'paypal_plus',
        'isRedirect' => true,
    ];

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
     *
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
     *
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
