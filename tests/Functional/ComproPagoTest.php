<?php

namespace Shoperti\Tests\PayMe\Functional;

use Shoperti\PayMe\PayMe;

class ComproPagoTest extends AbstractFunctionalTestCase
{
    protected $gateway;
    protected $options = [
        'email' => 'john@doe.com',
        'name'  => 'John Doe',
    ];

    public function setUp()
    {
        parent::setUp();

        $this->gateway = PayMe::make($this->credentials['compro_pago']);
    }

    /** @test */
    public function it_should_create_a_new_conekta_gateway()
    {
        $this->assertInstanceOf('Shoperti\PayMe\Gateways\ComproPago\ComproPagoGateway', $this->gateway->getGateway());
        $this->assertInstanceOf('Shoperti\PayMe\Gateways\ComproPago\Charges', $this->gateway->charges());
    }

    /** @test */
    public function is_should_succeed_to_charge_a_token()
    {
        $charge = $this->gateway->charges()->create(1000, 'oxxo', $this->options);

        $this->assertTrue($charge->success());
    }

    /** @test */
    public function is_should_succeed_to_charge_a_token_with_params()
    {
        $charge = $this->gateway->charges()->create(1000, 'oxxo', $this->options + [
            'reference' => 'order_1',
        ]);

        $response = $charge->data();

        $this->assertTrue($charge->success());
        $this->assertSame($response['order_info']['order_id'], 'order_1');
    }

    /** @test */
    public function it_should_fail_with_invalid_access_key()
    {
        $gateway = PayMe::make(array_merge($this->credentials['compro_pago'], ['private_key' => 'invalid_key']));

        $charge = $gateway->charges()->create(1000, 'oxxo', $this->options);

        $this->assertSame($charge->message(), 'Acceso no Autorizado');
    }

    /** @test */
    public function it_can_retrieve_a_single_event()
    {
        $charge = $this->gateway->charges()->create(1500, 'oxxo', $this->options);

        $event = $this->gateway->events()->find($charge->reference());

        $response = $event->data();

        $this->assertSame($response['amount'], '15.0');
    }
}
