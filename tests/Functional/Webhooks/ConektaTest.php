<?php

namespace Shoperti\Tests\PayMe\Functional\Webhooks;

class ConektaTest extends AbstractTest
{
    protected $gatewayData = [
        'config' => 'conekta',
    ];

    /** @test */
    public function it_should_get_all_hooks()
    {
        $webhooks = $this->getPayMe()->webhooks()->all();

        $this->assertFalse(array_key_exists('success', $webhooks));
    }

    /**
     * @test
     * depends it_should_get_all_hooks
     */
    public function it_should_create_and_delete_a_webhook()
    {
        $hooks = $this->getPayMe()->webhooks();

        $webhooks = $hooks->all();
        if (is_array($webhooks) && count($webhooks) === 10) {
            $deletable = $webhooks[count($webhooks) - 1];
            $hooks->delete($deletable['id']);
        }

        $url = 'http://payme.com/hook/'.time().'-'.rand(100, 999);

        $payload = [
            'events' => [
                'charge.created', 'charge.paid', 'charge.under_fraud_review',
                'charge.fraudulent', 'charge.refunded', 'charge.created',
                'charge.chargeback.created', 'charge.chargeback.updated',
                'charge.chargeback.under_review', 'charge.chargeback.lost',
                'charge.chargeback.won', 'subscription.created', 'subscription.paused',
                'subscription.resumed', 'subscription.canceled', 'subscription.expired',
                'subscription.updated', 'subscription.paid', 'subscription.payment_failed',
            ],
            'url'                 => $url,
            'production_enabled'  => 1,
            'development_enabled' => 1,
        ];

        $created = $hooks->create($payload)->data();

        $webhook = $hooks->find($created['id']);
        $hooks->delete($created['id']);

        $this->assertSame($created['url'], $webhook->data()['url']);
    }
}
