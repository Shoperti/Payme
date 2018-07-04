<?php

namespace Shoperti\Tests\PayMe\Functional;

use Shoperti\PayMe\PayMe;

class ComproPagoTest extends AbstractFunctionalTestCase
{
    protected $gateway;

    public function setUp()
    {
        parent::setUp();

        $this->gateway = PayMe::make($this->credentials['compro_pago']);
    }

    /** @test */
    public function it_should_create_a_new_gateway()
    {
        $this->assertInstanceOf(\Shoperti\PayMe\Gateways\ComproPago\ComproPagoGateway::class, $this->gateway->getGateway());
        $this->assertInstanceOf(\Shoperti\PayMe\Gateways\ComproPago\Charges::class, $this->gateway->charges());
    }

    /** @test */
    public function it_should_fail_to_generate_a_payment_with_invalid_access_key()
    {
        $orderData = $this->getOrderPayload();

        /** @var \Shoperti\PayMe\PayMe $gateway */
        $gateway = PayMe::make(array_merge($this->credentials['compro_pago'], ['private_key' => 'invalid_key']));

        $charge = $gateway->charges()->create($orderData['total'], 'oxxo', $orderData['payload']);

        $this->assertFalse($charge->success());
    }

    /** @test */
    public function it_should_succeed_to_generate_a_payment_with_min_data()
    {
        $orderData = $this->getOrderPayload();
        $payload = [
            'email' => $orderData['payload']['email'],
            'name'  => 'John Doe',
        ];

        $charge = $this->gateway->charges()->create($orderData['total'], 'oxxo', $payload);

        $this->assertTrue($charge->success());
    }

    /** @test */
    public function it_should_succeed_to_generate_a_payment()
    {
        $orderData = $this->getOrderPayload();

        $charge = $this->gateway->charges()->create($orderData['total'], 'oxxo', $orderData['payload']);

        $response = $charge->data();

        $this->assertTrue($charge->success());
        $this->assertSame($orderData['payload']['reference'], $response['order_info']['order_id']);

        return $charge;
    }

    /**
     * @test
     * @depends it_should_succeed_to_generate_a_payment
     *
     * @param array $charge
     */
    public function it_should_retrieve_an_event($charge)
    {
        $event = $this->gateway->events()->find($charge->reference());

        $response = $event->data();
        $chargeResponse = $charge->data();

        $this->assertTrue($event->success());
        $this->assertEquals($chargeResponse['amount'], $response['amount']);
    }

    /** @test */
    public function it_should_create_a_webhook()
    {
        $payload = [
            'url' => 'https://httpbin.org/post?rand='.rand(0, getrandmax()),
        ];

        /** @var \Shoperti\PayMe\Contracts\WebhookInterface $hooksManager */
        $hooksManager = $this->gateway->webhooks();

        /** @var \Shoperti\PayMe\Contracts\ResponseInterface $response */
        $response = $hooksManager->create($payload);

        $data = $response->data();

        $this->assertSame($payload['url'], $data['url']);

        return $data;
    }

    /**
     * @test
     * @depends it_should_create_a_webhook
     *
     * @param array $dataAndAmount
     */
    public function it_should_get_all_webhooks($data)
    {
        /** @var \Shoperti\PayMe\Contracts\WebhookInterface $hooksManager */
        $hooksManager = $this->gateway->webhooks();

        /** @var \Shoperti\PayMe\Contracts\ResponseInterface $response */
        $response = $hooksManager->all();

        $items = [];
        foreach ($response as $responseItem) {
            $items[] = $responseItem->data()['id'];
        }

        $this->assertGreaterThan(0, count($items));
        $this->assertContains($data['id'], $items);

        return $items;
    }

    /**
     * @test
     * @depends it_should_get_all_webhooks
     *
     * @param array $ids
     */
    public function it_should_delete_webhooks($ids)
    {
        /** @var \Shoperti\PayMe\Contracts\WebhookInterface $hooksManager */
        $hooksManager = $this->gateway->webhooks();

        foreach ($ids as $id) {
            $response = $hooksManager->delete($id);
            $this->assertSame($id, $response->data()['id']);
        }
    }

    protected function getOrderPayload(array $customData = [])
    {
        $microtime = str_replace('.', '', ''.microtime(true));

        return parent::getOrderPayload(['email' => "customer$microtime@example.com"]);
    }
}
