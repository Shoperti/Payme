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
    public function is_should_succeed_to_charge_a_token()
    {
        $gateway = PayMe::make($this->credentials['conekta']);

        $charge = $gateway->charges()->create(1000, 'tok_test_visa_4242');

        $this->assertTrue($charge->success());
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

        $charge = $gateway->charges()->create(1000, 'tok_test_visa_4242', [
            'reference'  => 'order_1',
            'name'       => 'TheCustomerName',
            'email'      => 'customer@email.com',
            'phone'      => '55555555',
            'line_items' => [
                [
                    'name'        => 'Box of Cohiba S1s',
                    'description' => 'Imported From Mex.',
                    'unit_price'  => 20000,
                    'quantity'    => 1,
                    'sku'         => 'cohb_s1',
                ],
                [
                    'name'        => 'Basic Toothpicks',
                    'description' => 'Wooden',
                    'unit_price'  => 100,
                    'quantity'    => 250,
                    'sku'         => 'tooth_r3',
                ],
            ],
            'billing_address' => [
                'address1' => 'Rio Missisipi #123',
                'address2' => 'Paris',
                'city'     => 'Guerrero',
                'country'  => 'Mexico',
                'zip'      => '01085',
            ],
            'shipping_address' => [
                'address1' => '33 Main Street',
                'address2' => 'Apartment 3',
                'city'     => 'Wanaque',
                'country'  => 'USA',
                'state'    => 'NJ',
                'zip'      => '01085',
                'price'    => 100,
                'carrier'  => 'payme',
                'service'  => 'pending',
            ],
        ]);

        $response = $charge->data();

        $this->assertTrue($charge->success());
        $this->assertSame($response['details']['shipment']['address']['city'], 'Wanaque');
        $this->assertSame($response['details']['line_items'][1]['description'], 'Wooden');
        $this->assertSame($response['details']['name'], 'TheCustomerName');
        $this->assertSame($response['details']['billing_address']['city'], 'Guerrero');
    }

    /** @test */
    public function it_sould_fail_with_invalid_access_key()
    {
        $gateway = PayMe::make(array_merge($this->credentials['conekta'], ['private_key' => 'invalid_key']));

        $charge = $gateway->charges()->create(1000, 'tok_test_card_declined');

        $this->assertSame($charge->message(), 'Acceso no autorizado.');
    }

    /** @test */
    public function it_can_retrieve_a_single_and_all_events()
    {
        $gateway = PayMe::make($this->credentials['conekta']);

        $events = $gateway->events()->all();

        $this->assertNotEmpty($events[0]->data()['data']);
        $this->assertInternalType('array', $events[0]->data()['data']);

        $event = $gateway->events()->find($events[0]->reference());
    }
}
