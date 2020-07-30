<?php

namespace Shoperti\Tests\PayMe\Functional;

use Shoperti\PayMe\Gateways\Conekta\Charges;
use Shoperti\PayMe\Gateways\Conekta\ConektaGateway;

class ConektaTest extends AbstractFunctionalTestCase
{
    protected $gatewayData = [
        'config'  => 'conekta',
        'gateway' => ConektaGateway::class,
        'charges' => Charges::class,
    ];

    /** @test */
    public function it_should_create_a_new_customer()
    {
        $phone = '+525511223344';

        $customer = $this->getPayMe()->customers()->create([
            'name'  => 'Jimmi Hendrix',
            'email' => 'jimmihendrix21@gmail.com',
            'phone' => $phone,
            'card'  => 'tok_test_visa_4242',
        ]);

        $response = $customer->data();

        $this->assertTrue($customer->success());
        $this->assertequals('Jimmi Hendrix', $response['name']);
        $this->assertequals('jimmihendrix21@gmail.com', $response['email']);
        $this->assertequals($phone, $response['phone']);
        $this->assertArrayHasKey('payment_sources', $response);

        return $response;
    }

    /**
     * @test
     * @depends it_should_create_a_new_customer
     */
    public function it_should_succeed_to_charge_an_order_with_customer_token($data)
    {
        $order = $this->getOrderData();

        $total = $order['total'];
        $payload = $order['payload'];

        $charge = $this->getPayMe()->charges()->create($total, $data['id'], $payload);

        $response = $charge->data();

        $this->assertTrue($charge->success());
        $this->assertSame($payload['shipping_address']['city'], $response['shipping_contact']['address']['city']);
        $this->assertSame(2, count($response['line_items']['data']));
        $this->assertNotSame($response['line_items']['data'][0]['description'], $response['line_items']['data'][1]['description']);
        $this->assertSame($payload['name'], $response['customer_info']['name']);

        return $response;
    }

    /**
     * @test
     * @depends it_should_create_a_new_customer
     */
    public function it_should_find_a_customer($data)
    {
        $customer = $this->getPayMe()->customers()->find($data['id']);

        $response = $customer->data();

        $this->assertTrue($customer->success());
        $this->assertEquals($response['id'], $data['id']);
        $this->assertEquals($response['default_payment_source_id'], $data['payment_sources']['data'][0]['id']);

        return $response;
    }

    /**
     * @test
     * @depends it_should_find_a_customer
     */
    public function it_should_create_a_customer_card($data)
    {
        $card = $this->getPayMe()->cards()->create('tok_test_mastercard_4444', [
            'customer' => $data['id'],
        ]);

        $response = $card->data();

        $this->assertTrue($card->success());
        $this->assertEquals('payment_source', $response['object']);
        $this->assertEquals('card', $response['type']);

        return [
            'customer' => $data,
            'card'     => $response,
        ];
    }

    /**
     * @test
     * @depends it_should_create_a_customer_card
     */
    public function it_should_update_a_customer($data)
    {
        $newName = 'Alice Cooper';

        $customer = $this->getPayMe()->customers()->update($data['customer']['id'], [
            'name'         => $newName,
            'default_card' => $data['card']['id'],
        ]);

        $response = $customer->data();

        $this->assertTrue($customer->success());
        $this->assertEquals($newName, $response['name']);
        $this->assertEquals($data['card']['id'], $response['default_payment_source_id']);

        return $data['customer'];
    }

    /**
     * @test
     * @depends it_should_update_a_customer
     */
    public function it_should_delete_a_customer_card($data)
    {
        $card = $this->getPayMe()->cards()->delete($data['id'], [
            'card_id' => $data['payment_sources']['data'][0]['id'],
        ]);

        $response = $card->data();

        $this->assertTrue($card->success());
        $this->assertEquals(1, $response['deleted']);
    }

    /** @test */
    public function it_should_fail_to_charge_an_incomplete_order()
    {
        $charge = $this->getPayMe()->charges()->create(1000, 'unused_token');

        $response = $charge->data();

        $this->assertFalse($charge->success());
        $this->assertEquals('config_error', $charge->errorCode);

        $this->assertEquals('error', $response['object']);
    }

    /** @test */
    public function it_should_fail_to_charge_an_order_with_invalid_card_token()
    {
        $order = $this->getOrderData();
        $total = $order['total'];
        $payload = $order['payload'];

        $charge = $this->getPayMe()->charges()->create($total, 'tok_test_card_declined', $payload);

        $response = $charge->data();

        $this->assertFalse($charge->success());
        $this->assertEquals('card_declined', $charge->errorCode);
        $this->assertEquals('error', $response['object']);
    }

    /** @test */
    public function it_should_fail_to_charge_an_order_with_insufficient_funds_token()
    {
        $order = $this->getOrderData();
        $total = $order['total'];
        $payload = $order['payload'];

        $charge = $this->getPayMe()->charges()->create($total, 'tok_test_insufficient_funds', $payload);

        $response = $charge->data();

        $this->assertFalse($charge->success());
        $this->assertEquals('insufficient_funds', $charge->errorCode);
        $this->assertEquals('error', $response['object']);
    }

    /** @test */
    public function it_should_succeed_to_generate_a_charge_with_oxxo()
    {
        $order = $this->getOrderData();
        $total = $order['total'];
        $payload = $order['payload'];

        $charge = $this->getPayMe()->charges()->create($total, 'oxxo_cash', $payload);

        $response = $charge->data();

        $this->assertTrue($charge->success());
        $this->assertSame($payload['shipping_address']['city'], $response['shipping_contact']['address']['city']);
        $this->assertSame(2, count($response['line_items']['data']));
        $this->assertNotSame($response['line_items']['data'][0]['description'], $response['line_items']['data'][1]['description']);
        $this->assertSame($payload['name'], $response['customer_info']['name']);
        $this->assertSame($charge->reference(), $response['charges']['data'][0]['id']);
        $this->assertSame($charge->authorization(), $response['charges']['data'][0]['payment_method']['reference']);

        return $response;
    }

    /** @test */
    public function it_should_succeed_to_generate_a_charge_with_spei()
    {
        $order = $this->getOrderData();
        $total = $order['total'];
        $payload = $order['payload'];

        $charge = $this->getPayMe()->charges()->create($total, 'spei', $payload);

        $response = $charge->data();

        $this->assertTrue($charge->success());
        $this->assertSame($payload['shipping_address']['city'], $response['shipping_contact']['address']['city']);
        $this->assertSame(2, count($response['line_items']['data']));
        $this->assertNotSame($response['line_items']['data'][0]['description'], $response['line_items']['data'][1]['description']);
        $this->assertSame($payload['name'], $response['customer_info']['name']);
        $this->assertSame($charge->reference(), $response['charges']['data'][0]['id']);
        $this->assertSame($charge->authorization(), $response['charges']['data'][0]['payment_method']['clabe']);

        return $response;
    }

    /** @test */
    public function it_should_succeed_to_charge_an_order_with_card_token()
    {
        $order = $this->getOrderData();
        $total = $order['total'];
        $payload = $order['payload'];

        $charge = $this->getPayMe()->charges()->create($total, 'tok_test_visa_4242', $payload);

        $response = $charge->data();

        $this->assertTrue($charge->success());
        $this->assertSame($payload['shipping_address']['city'], $response['shipping_contact']['address']['city']);
        $this->assertSame(2, count($response['line_items']['data']));
        $this->assertNotSame($response['line_items']['data'][0]['description'], $response['line_items']['data'][1]['description']);
        $this->assertSame($payload['name'], $response['customer_info']['name']);
        $this->assertSame($charge->reference(), $response['charges']['data'][0]['id']);
        $this->assertSame($charge->authorization(), $response['charges']['data'][0]['payment_method']['auth_code']);

        return $response;
    }

    /**
     * @test
     * @depends it_should_succeed_to_charge_an_order_with_card_token
     */
    public function it_should_succeed_to_fully_refund_a_charge($prevResponse)
    {
        $refund = $this->getPayMe()->charges()->refund(null, $prevResponse['id'], [
            'currency' => 'MXN',
            'reason'   => 'requested_by_client',
        ]);

        $response = $refund->data();

        $this->assertTrue($refund->success());
        $this->assertSame('refund', $refund->type());
        $this->assertSame($prevResponse['amount'], $response['amount_refunded']);
        $this->assertSame($refund->reference(), $response['charges']['data'][0]['refunds']['data'][0]['id']);
        $this->assertSame($refund->authorization(), $response['charges']['data'][0]['refunds']['data'][0]['auth_code']);
    }

    /**
     * @test
     * @depends it_should_succeed_to_charge_an_order_with_card_token
     */
    public function it_should_succeed_to_partially_refund_a_charge()
    {
        $gateway = $this->getPayMe();
        $order = $this->getOrderData();

        $charge = $gateway->charges()->create($order['total'], 'tok_test_visa_4242', $order['payload']);
        $prevResponse = $charge->data();

        $refund = $gateway->charges()->refund(5000, $prevResponse['id'], [
            'currency' => 'MXN',
            'reason'   => 'requested_by_client',
        ]);

        $response = $refund->data();

        $this->assertTrue($refund->success());
        $this->assertSame('refund', $refund->type());
        $this->assertSame(5000, $response['amount_refunded']);
        $this->assertSame($refund->reference(), $response['charges']['data'][0]['refunds']['data'][0]['id']);
        $this->assertSame($refund->authorization(), $response['charges']['data'][0]['refunds']['data'][0]['auth_code']);
    }

    /** @test */
    public function it_should_fail_with_invalid_access_key()
    {
        $gateway = $this->getPayMe(['private_key' => 'invalid_key']);

        $charge = $gateway->charges()->create(1000, 'tok_test_visa_4242');

        $this->assertSame($charge->message(), 'Acceso no autorizado.');
    }

    /** @test */
    public function it_should_retrieve_all_events()
    {
        $events = $this->getPayMe()->events()->all();

        $this->assertNotEmpty($events[0]->data()['data']);
        $this->assertInternalType('array', $events[0]->data()['data']);

        return $events;
    }

    /**
     * @test
     * @depends it_should_retrieve_all_events
     */
    public function it_should_retrieve_a_single_event($events)
    {
        $event = $this->getPayMe()->events()->find($events[0]->data()['id']);

        $this->assertCount(1, $event);
    }

    /** @test */
    public function it_should_fail_to_retrieve_a_nonexistent_event()
    {
        $event = $this->getPayMe()->events()->find('qiq');

        $this->assertEquals('failed', $event->status);
    }

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
