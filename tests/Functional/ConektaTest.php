<?php

namespace Shoperti\Tests\PayMe\Functional;

use Shoperti\PayMe\PayMe;

class ConektaTest extends AbstractFunctionalTestCase
{
    /** @test */
    public function it_should_create_a_new_conekta_gateway()
    {
        $gateway = PayMe::make($this->credentials['conekta']);

        $this->assertInstanceOf('Shoperti\PayMe\Gateways\Conekta\ConektaGateway', $gateway->getGateway());
        $this->assertInstanceOf('Shoperti\PayMe\Gateways\Conekta\Charges', $gateway->charges());
    }

    /** @test */
    public function it_should_create_a_new_customer()
    {
        $gateway = PayMe::make($this->credentials['conekta']);

        $customer = $gateway->customers()->create([
            'name'  => 'Jimmi Hendrix',
            'email' => 'jimmihendrix21@gmail.com',
            'phone' => '+525511223344',
            'card'  => 'tok_test_visa_4242',
        ]);

        $response = $customer->data();

        $this->assertTrue($customer->success());
        $this->assertequals('Jimmi Hendrix', $response['name']);
        $this->assertequals('jimmihendrix21@gmail.com', $response['email']);
        $this->assertequals('+525511223344', $response['phone']);
        $this->assertArrayHasKey('payment_sources', $response);

        return $response;
    }

    /**
     * @test
     * @depends it_should_create_a_new_customer
     */
    public function it_should_find_a_customer($data)
    {
        $gateway = PayMe::make($this->credentials['conekta']);

        $customer = $gateway->customers()->find($data['id']);

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
        $gateway = PayMe::make($this->credentials['conekta']);

        $card = $gateway->cards()->create('tok_test_mastercard_4444', [
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
        $gateway = PayMe::make($this->credentials['conekta']);

        $newName = 'Alice Cooper';

        $customer = $gateway->customers()->update($data['customer']['id'], [
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
        $gateway = PayMe::make($this->credentials['conekta']);

        $card = $gateway->cards()->delete($data['id'], [
            'card_id' => $data['payment_sources']['data'][0]['id'],
        ]);

        $response = $card->data();

        $this->assertTrue($card->success());
        $this->assertEquals(1, $response['deleted']);
    }

    /** @test */
    public function is_should_fail_to_charge_a_token()
    {
        $gateway = PayMe::make($this->credentials['conekta']);

        $charge = $gateway->charges()->create(1000, 'tok_test_card_declined');

        $this->assertFalse($charge->success());
    }

    /** @test */
    public function is_should_succeed_to_charge_a_token_with_params()
    {
        $gateway = PayMe::make($this->credentials['conekta']);

        $charge = $gateway->charges()->create(10000, 'tok_test_visa_4242', [
            'reference'  => 'order_1',
            'name'       => 'John Doe',
            'email'      => 'customer@email.com',
            'phone'      => '+525544443333',
            'line_items' => [
                [
                    'name'        => 'Box of Cohiba S1s',
                    'description' => 'Imported From Mex.',
                    'unit_price'  => 5000,
                    'quantity'    => 1,
                    'sku'         => 'cohb_s1',
                ],
                [
                    'name'        => 'Basic Toothpicks',
                    'description' => 'Wooden',
                    'unit_price'  => 500,
                    'quantity'    => 10,
                    'sku'         => 'tooth_r3',
                ],
            ],
            'billing_address' => [
                'address1' => 'Rio Missisipi #123',
                'address2' => 'Paris',
                'city'     => 'Guerrero',
                'country'  => 'MX',
                'state'    => 'DF',
                'zip'      => '01085',
            ],
            'shipping_address' => [
                'address1' => '33 Main Street',
                'address2' => 'Apartment 3',
                'city'     => 'Wanaque',
                'country'  => 'US',
                'state'    => 'NJ',
                'zip'      => '01085',
                'price'    => 0,
                'carrier'  => 'payme',
                'service'  => 'pending',
            ],
        ]);

        $response = $charge->data();

        $this->assertTrue($charge->success());
        $this->assertSame($response['shipping_contact']['address']['city'], 'Wanaque');
        $this->assertSame(2, count($response['line_items']['data']));
        $this->assertNotSame($response['line_items']['data'][0]['description'], $response['line_items']['data'][1]['description']);
        $this->assertSame($response['customer_info']['name'], 'John Doe');

        return $response;
    }

    /**
     * @test
     * @depends is_should_succeed_to_charge_a_token_with_params
     */
    public function is_should_succeed_to_refund_a_charge($response)
    {
        $gateway = PayMe::make($this->credentials['conekta']);

        $charge = $gateway->charges()->refund(1000, $response['id'], [
            'currency'   => 'MXN',
            'reason'     => 'requested_by_client',
            'line_items' => [
                [
                    'name'        => 'Box of Cohiba S1s',
                    'description' => 'Imported From Mex.',
                    'unit_price'  => 5000,
                    'quantity'    => 1,
                    'sku'         => 'cohb_s1',
                ],
                [
                    'name'        => 'Basic Toothpicks',
                    'description' => 'Wooden',
                    'unit_price'  => 500,
                    'quantity'    => 10,
                    'sku'         => 'tooth_r3',
                ],
            ],
        ]);

        $response = $charge->data();

        $this->assertTrue($charge->success());
        // previous test creates a charge of 1000
        $this->assertSame($response['amount_refunded'], 10000);
    }

    /** @test */
    public function it_should_fail_with_invalid_access_key()
    {
        $gateway = PayMe::make(array_merge($this->credentials['conekta'], ['private_key' => 'invalid_key']));

        $charge = $gateway->charges()->create(1000, 'tok_test_card_declined');

        $this->assertSame($charge->message(), 'Acceso no autorizado.');
    }

    /** @test */
    public function it_should_retrieve_a_single_and_all_events()
    {
        $gateway = PayMe::make($this->credentials['conekta']);

        $events = $gateway->events()->all();

        $this->assertNotEmpty($events[0]->data()['data']);
        $this->assertInternalType('array', $events[0]->data()['data']);

        $event = $gateway->events()->find($events[0]->data()['id']);
    }

    /** @test */
    public function it_should_get_all_hooks()
    {
        $gateway = PayMe::make($this->credentials['conekta']);

        $webhooks = $gateway->webhooks()->all();

        $this->assertFalse(array_key_exists('success', $webhooks));
    }

     /**
      * @test
      * depends it_should_get_all_hooks
      */
     public function it_should_create_and_delete_a_webhook()
     {
         $gateway = PayMe::make($this->credentials['conekta']);

         $webhooks = $gateway->webhooks()->all();
         if (count($webhooks) === 10) {
             $deletable = $webhooks[count($webhooks) - 1];
             $gateway->webhooks()->delete($deletable['id']);
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

         $created = $gateway->webhooks()->create($payload)->data();

         $webhook = $gateway->webhooks()->find($created['id']);
         $gateway->webhooks()->delete($created['id']);

         $this->assertSame($created['url'], $webhook->data()['url']);
     }
}
