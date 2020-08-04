<?php

namespace Shoperti\Tests\PayMe\Functional\Charges;

class ConektaTest extends AbstractTest
{
    protected $gatewayData = [
        'config' => 'conekta',
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
        $charge = $this->successfulChargeRequest($data['id']);

        $response = $charge->data();
        list($_, $payload) = array_values($this->getOrderData());

        $this->assertEquals('order', $charge->type());
        $this->assertEquals('paid', $charge->status());
        $this->assertEquals($response['charges']['data'][0]['id'], $charge->reference());
        $this->assertEquals($response['charges']['data'][0]['payment_method']['auth_code'], $charge->authorization());

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
        $charge = $this->chargeRequest('tok_test_card_declined');

        $response = $charge->data();

        $this->assertFalse($charge->success());
        $this->assertEquals('card_declined', $charge->errorCode);
        $this->assertEquals('error', $response['object']);
    }

    /** @test */
    public function it_should_fail_to_charge_an_order_with_insufficient_funds_token()
    {
        $charge = $this->chargeRequest('tok_test_insufficient_funds');

        $response = $charge->data();

        $this->assertFalse($charge->success());
        $this->assertEquals('insufficient_funds', $charge->errorCode);
        $this->assertEquals('error', $response['object']);
    }

    /** @test */
    public function it_should_succeed_to_generate_a_charge_with_oxxo()
    {
        $charge = $this->successfulChargeRequest('oxxo_cash');

        $response = $charge->data();
        list($_, $payload) = array_values($this->getOrderData());

        $this->assertEquals('order', $charge->type());
        $this->assertEquals('pending', $charge->status());
        $this->assertSame($response['charges']['data'][0]['id'], $charge->reference());
        $this->assertSame($response['charges']['data'][0]['payment_method']['reference'], $charge->authorization());

        $this->assertSame($payload['shipping_address']['city'], $response['shipping_contact']['address']['city']);
        $this->assertSame(2, count($response['line_items']['data']));
        $this->assertNotSame($response['line_items']['data'][0]['description'], $response['line_items']['data'][1]['description']);
        $this->assertSame($payload['name'], $response['customer_info']['name']);

        return $response;
    }

    /** @test */
    public function it_should_succeed_to_generate_a_charge_with_spei()
    {
        $charge = $this->successfulChargeRequest('spei');

        $response = $charge->data();
        list($_, $payload) = array_values($this->getOrderData());

        $this->assertEquals('order', $charge->type());
        $this->assertEquals('pending', $charge->status());
        $this->assertSame($response['charges']['data'][0]['id'], $charge->reference());
        $this->assertSame($response['charges']['data'][0]['payment_method']['clabe'], $charge->authorization());

        $this->assertSame($payload['shipping_address']['city'], $response['shipping_contact']['address']['city']);
        $this->assertSame(2, count($response['line_items']['data']));
        $this->assertNotSame($response['line_items']['data'][0]['description'], $response['line_items']['data'][1]['description']);
        $this->assertSame($payload['name'], $response['customer_info']['name']);

        return $response;
    }

    /** @test */
    public function it_should_succeed_to_charge_an_order_with_card_token()
    {
        $charge = $this->successfulChargeRequest('tok_test_visa_4242');

        $response = $charge->data();
        list($_, $payload) = array_values($this->getOrderData());

        $this->assertEquals('order', $charge->type());
        $this->assertEquals('paid', $charge->status());
        $this->assertSame($response['charges']['data'][0]['id'], $charge->reference());
        $this->assertSame($response['charges']['data'][0]['payment_method']['auth_code'], $charge->authorization());

        $this->assertSame($payload['shipping_address']['city'], $response['shipping_contact']['address']['city']);
        $this->assertSame(2, count($response['line_items']['data']));
        $this->assertNotSame($response['line_items']['data'][0]['description'], $response['line_items']['data'][1]['description']);
        $this->assertSame($payload['name'], $response['customer_info']['name']);

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
}
