<?php

namespace Shoperti\Tests\PayMe\Functional;

use Shoperti\PayMe\PayMe;

class PaypalExpressTest extends AbstractFunctionalTestCase
{
    protected $gateway;

    public function setUp()
    {
        parent::setUp();

        $this->gateway = PayMe::make($this->credentials['paypal']);
    }

    /** @test */
    public function it_should_create_a_new_paypal_express_gateway()
    {
        $this->assertInstanceOf('Shoperti\PayMe\Gateways\PaypalExpress\PaypalExpressGateway', $this->gateway->getGateway());
        $this->assertInstanceOf('Shoperti\PayMe\Gateways\PaypalExpress\Charges', $this->gateway->charges());
    }

    /** @test */
    public function it_should_succeed_to_create_a_charge()
    {
        $orderData = $this->getOrderPayload();
        $charge = $this->gateway->charges()->create($orderData['total'], 'SetExpressCheckout', $orderData['payload']);

        $this->assertFalse($charge->success());
        $this->assertTrue($charge->isRedirect());
        $this->assertContains('https://www.sandbox.paypal.com/cgi-bin/webscr', $charge->authorization());
    }

    /** @test */
    public function it_should_fail_to_create_a_charge()
    {
        $orderData = $this->getOrderPayload();
        $charge = $this->gateway->charges()->create($orderData['total'] - 100, 'SetExpressCheckout', $orderData['payload']);

        $this->assertFalse($charge->success());
        $this->assertTrue($charge->isRedirect());
        $this->assertContains('https://www.sandbox.paypal.com/cgi-bin/webscr', $charge->authorization());
    }

    /** @test */
    public function it_should_validate_event()
    {
        $event = $this->gateway->events()->find('txn_id', [
            'payment_type'         => 'instant',
            'payment_date'         => 'Thu Sep 28 2017 22:16:01 GMT-0500 (CDT)',
            'payment_status'       => 'Completed',
            'address_status'       => 'confirmed',
            'payer_status'         => 'verified',
            'first_name'           => 'John',
            'last_name'            => 'Smith',
            'payer_email'          => 'buyer@paypalsandbox.com',
            'payer_id'             => 'TESTBUYERID01',
            'address_name'         => 'John Smith',
            'address_country'      => 'United States',
            'address_country_code' => 'US',
            'address_zip'          => '95131',
            'address_state'        => 'CA',
            'address_city'         => 'San Jose',
            'address_street'       => '123 any street',
            'business'             => 'seller@paypalsandbox.com',
            'receiver_email'       => 'seller@paypalsandbox.com',
            'receiver_id'          => 'seller@paypalsandbox.com',
            'residence_country'    => 'US',
            'item_name1'           => 'something',
            'item_number1'         => 'AK-1234',
            'tax'                  => '2.02',
            'mc_currency'          => 'USD',
            'mc_fee'               => '0.44',
            'mc_gross'             => '12.34',
            'mc_gross_1'           => '12.34',
            'mc_handling'          => '2.06',
            'mc_handling1'         => '1.67',
            'mc_shipping'          => '3.02',
            'mc_shipping1'         => '1.02',
            'txn_type'             => 'cart',
            'txn_id'               => '195047926',
            'notify_version'       => '2.1',
            'custom'               => 'xyz123',
            'invoice'              => 'abc1234',
            'test_ipn'             => '1',
            'verify_sign'          => 'AFcWxV21C7fd0v3bYYYRCpSSRl31AT-k5RWJQQ1ltu6rslsRc91idKIv',
        ]);

        $this->assertTrue($event->success());
        $this->assertArrayHasKey('VERIFIED', $event->data());
    }
}
