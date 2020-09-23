<?php

namespace Shoperti\Tests\PayMe\Functional\Webhooks;

class OpenPayTest extends AbstractTest
{
    protected $gatewayData = [
        'config' => 'open_pay',
    ];

    /** @test */
    public function it_should_create_get_and_delete_a_webhook()
    {
        $url = 'https://httpbin.org/post';

        $gateway = $this->getPayMe();

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

        $this->assertTrue($response->success());

        $data = $response->data();

        $webhook = $openPayHooks->find($data['id']);

        $openPayHooks->delete($data['id']);

        $this->assertSame($data['url'], $webhook->data()['url']);
    }
}
