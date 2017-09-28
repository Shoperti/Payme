<?php

namespace Shoperti\Tests\PayMe\Functional;

use Shoperti\PayMe\PayMe;

class MercadoPagoBasicTest extends AbstractFunctionalTestCase
{
    protected $gateway;

    public function setUp()
    {
        parent::setUp();

        $this->gateway = PayMe::make($this->credentials['mercadopago_basic']);
    }

    /** @test */
    public function it_should_create_a_new_mercadopago_basic_gateway()
    {
        $this->assertInstanceOf('Shoperti\PayMe\Gateways\MercadoPagoBasic\MercadoPagoBasicGateway', $this->gateway->getGateway());
        $this->assertInstanceOf('Shoperti\PayMe\Gateways\MercadoPagoBasic\Charges', $this->gateway->charges());
    }

    /** @test */
    public function is_should_succeed_to_create_a_charge()
    {
        $charge = $this->gateway->charges()->create(11010, 'regular_payment', [
            'return_url'  => 'http://localhost/return',
            'cancel_url'  => 'http://localhost/cancel',
            'notify_url'  => 'http://localhost/notify',
            'description' => 'PayMe Payment',
            'reference'   => 'order_1',
            'currency'    => 'MXN',
            'name'        => 'TheCustomerName',
            'email'       => 'customer@email.com',
            'phone'       => '55555555',
            'line_items'  => [
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
                'zip'      => '01085',
            ],
            'shipping_address' => [
                'address1' => '123 NW Blvd',
                'address2' => 'Lynx Lane',
                'city'     => 'Topeka',
                'country'  => 'US',
                'state'    => 'KS',
                'zip'      => '66605',
                'price'    => 1000,
                'carrier'  => 'payme',
                'service'  => 'pending',
            ],
        ]);

        $this->assertFalse($charge->success());
        $this->assertTrue($charge->isRedirect());
        $this->assertContains('https://sandbox.mercadopago.com/mlm/checkout/pay', $charge->authorization());
    }

    /** @test */
    public function is_should_fail_to_create_a_charge()
    {
        $charge = $this->gateway->charges()->create(1000, 'regular_payment', [
            'return_url' => 'http://localhost/return',
            'cancel_url' => 'http://localhost/cancel',
            'notify_url' => 'http://localhost/notify',
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

        $this->assertFalse($charge->success());
        $this->assertTrue($charge->isRedirect());
    }
}
