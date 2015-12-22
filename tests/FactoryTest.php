<?php

namespace Shoperti\Tests\PayMe;

use Shoperti\PayMe\PayMeFactory;

class FactoryTest extends \PHPUnit_Framework_TestCase
{
    protected $factory;

    protected function setUp()
    {
        $this->factory = new PayMeFactory();
    }

    /**
     * @test
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage A gateway must be specified.
     */
    public function it_throws_if_no_driver_specified()
    {
        $this->factory->make([]);
    }

    /**
     * @test
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Unsupported gateway [bar].
     */
    public function it_thorws_on_not_gateway_supported()
    {
        $this->factory->factory(['driver' => 'bar']);
    }

    /** @test */
    public function it_can_create_a_new_factory_instance()
    {
        $gateway = $this->factory->make(['driver' => 'bogus']);

        $this->assertInstanceOf('Shoperti\PayMe\PayMe', $gateway);
        $this->assertInstanceOf('Shoperti\PayMe\Gateways\Bogus\BogusGateway', $gateway->getGateway());
    }

    /** @test */
    public function it_can_create_multiple_gateways()
    {
        $gatewayA = $this->factory->make(['driver' => 'bogus']);
        $gatewayC = $this->factory->make(['driver' => 'stripe', 'private_key' => '']);

        $this->assertInstanceOf('Shoperti\PayMe\Gateways\Bogus\BogusGateway', $gatewayA->getGateway());
        $this->assertInstanceOf('Shoperti\PayMe\Gateways\Stripe\StripeGateway', $gatewayC->getGateway());
    }

    /** @test */
    public function it_can_create_multiple_gateways_instances()
    {
        $gatewayA = $this->factory->make(['driver' => 'bogus']);
        $gatewayB = $this->factory->make(['driver' => 'bogus']);

        $this->assertSame($gatewayA->getGateway(), $gatewayB->getGateway());
    }

    /** @test */
    public function it_can_create_a_multiple_payme_instances_with_different_config()
    {
        $gatewayA = $this->factory->make(['driver' => 'bogus', 'foo' => 'bar'])->getGateway();
        $gatewayB = $this->factory->make(['driver' => 'bogus', 'bar' => 'foo'])->getGateway();

        $this->assertNotSame($gatewayA->getConfig(), $gatewayB->getConfig());
    }

    /** @test */
    public function it_can_call_an_api_class()
    {
        $gateway = $this->factory->make(['driver' => 'bogus']);

        $this->assertInstanceOf('Shoperti\PayMe\Gateways\Bogus\Charges', $gateway->charges());
    }
}
