<?php

namespace Shoperti\Tests\PayMe\Functional;

use Shoperti\PayMe\Gateways\SrPago\Charges;
use Shoperti\PayMe\Gateways\SrPago\Encryption;
use Shoperti\PayMe\Gateways\SrPago\SrPagoGateway;

class SrPagoTest extends AbstractFunctionalTestCase
{
    protected $gatewayData = [
        'config'  => 'sr_pago',
        'gateway' => SrPagoGateway::class,
        'charges' => Charges::class,
    ];

    /** @test */
    public function it_authenticates_current_application()
    {
        $gateway = $this->getPayMe()->getGateway();

        $response = $gateway->loginApplication();
        $this->assertNotNull($gateway->getConnectionToken());
    }

    /** @test */
    public function it_gets_a_valid_test_token()
    {
        $gateway = $this->getPayMe();

        $token = $this->getValidTestToken();

        $this->assertStringStartsWith('tok_', $token);
    }

    /** @test */
    public function it_should_succeed_to_charge_an_order_with_valid_token()
    {
        $payload = $this->getOrderPayload();
        $token = $this->getValidTestToken();

        $charge = $this->getPayMe()->charges()->create($payload['total'], $token, $payload['payload']);

        $response = $charge->data();

        $this->assertTrue($charge->success());
        $this->assertEquals('CARD', $charge->type());
        $this->assertEquals($charge->reference(), $response['result']['transaction']);
        $this->assertStringStartsWith('order_', $charge->reference());
        $this->assertEquals($charge->authorization(), $response['result']['recipe']['authorization_code']);
        $this->assertEquals('Transaction approved', $charge->message());
    }

    /** @test */
    public function it_should_fail_to_charge_with_invalid_card()
    {
        $payload = $this->getOrderPayload();

        $token = $this->getValidTestToken([
            'number' => '5504174401458735',
        ]);

        $charge = $this->getPayMe()->charges()->create($payload['total'], $token, $payload['payload']);

        $response = $charge->data();

        $this->assertFalse($charge->success());
        $this->assertNotNull($charge->message);
        $this->assertEquals('InvalidCardException', $charge['error']['code']);
        $this->assertEquals('card_declined', $charge->errorCode);
        $this->assertEquals('failed', $charge->status);
    }

    /** @test */
    public function it_should_succeed_to_create_a_new_customer()
    {
        $customer = $this->getPayMe()->customers()->create([
            'name'  => 'FSM',
            'email' => 'example@example.com',
        ]);

        $response = $customer->data();

        $this->assertStringStartsWith('cus_', $response['result']['id']);
        $this->assertSame('FSM', $response['result']['name']);
        $this->assertSame('example@example.com', $response['result']['email']);

        return $response;
    }

    /**
     * @test
     * @depends it_should_succeed_to_create_a_new_customer
     * */
    public function it_should_update_customer_information($data)
    {
        $customer = $this->getPayMe()->customers()->update($data['result']['id'], [
            'name'  => 'MSF',
            'email' => 'new@example.com',
        ]);

        $response = $customer->data();

        $this->assertSame('MSF', $response['result']['name']);
        $this->assertSame('new@example.com', $response['result']['email']);
        $this->assertSame($data['result']['id'], $response['result']['id']);

        return $response;
    }

    /**
     * @test
     * @depends it_should_succeed_to_create_a_new_customer
     */
    public function it_should_add_card_to_a_customer($data)
    {
        $token = $this->getValidTestToken([
            'cardholder_name' => 'Jon Doe',
            'number'          => '4242424242424242',
            'cvv'             => '123',
        ]);

        $customer = $this->getPayMe()->customers()->addCard($data['result']['id'], $token);

        $response = $customer->data();

        $this->assertStringStartsWith('crd_', $response['result']['token']);
        $this->assertSame('Jon Doe', $response['result']['holder_name']);
        $this->assertSame('VISA', $response['result']['type']);
        $this->assertStringStartsWith('4242', $response['result']['number']);
    }

    /**
     * @test
     * @depends it_should_succeed_to_create_a_new_customer
     */
    public function it_should_find_a_customer($data)
    {
        $customer = $this->getPayMe()->customers()->find($data['result']['id']);

        $response = $customer->data();

        $this->assertEquals($response['result']['id'], $data['result']['id']);
    }

    /**
     * @test
     * @depends it_should_succeed_to_create_a_new_customer
     */
    public function it_should_delete_a_customer($data)
    {
        $customer = $this->getPayMe()->customers()->delete($data['result']['id']);

        $response = $customer->data();

        $this->assertTrue($customer->success());
        $this->assertSame($data['result']['id'], $response['result']['id']);
    }

    /**
     * Return a valid token used for testing.
     *
     * @return string
     */
    private function getValidTestToken($attributes = [])
    {
        $gateway = $this->getPayMe()->getGateway();

        $card = array_merge([
            'cardholder_name' => 'FSMO',
            'number'          => '4242424242424242',
            'cvv'             => '123',
            'expiration'      => (new \DateTime('+1 year'))->format('ym'),
        ], $attributes);

        $params = Encryption::encryptParametersWithString($card);

        if (empty($gateway->getConnectionToken())) {
            $gateway->loginApplication();
        }

        $response = (new \GuzzleHttp\Client())->post('https://sandbox-api.srpago.com/v1/token', [
            'headers' => [
                'Authorization' => 'Bearer '.$gateway->getConnectionToken(),
                'Content-Type'  => 'application/json',
                'User-Agent'    => 'OpenPay PayMeBindings',
            ],
            'json' => $params,
        ]);

        return json_decode($response->getBody())->result->token;
    }

    /**
     * @test
     * @expectedException BadMethodCallException
     */
    public function it_throws_charge_complete_method_call()
    {
        $this->getPayMe()->charges()->complete();
    }

    /**
     * @test
     * @expectedException BadMethodCallException
     */
    public function it_throws_charge_refund_method_call()
    {
        $this->getPayMe()->charges()->refund(1000, 'ref');
    }
}
