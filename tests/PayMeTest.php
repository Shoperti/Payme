<?php

namespace Shoperti\Tests\PayMe;

use Shoperti\PayMe\PayMe;

class PayMeTest extends \PHPUnit_Framework_TestCase
{
    /** @test */
    public function it_gets_the_verson()
    {
        $gateway = PayMe::make(['driver' => 'bogus']);

        $this->assertSame(PayMe::VERSION, $gateway->getVersion());
    }

    /**
     * @test
     *
     * @expectedException InvalidArgumentException
     *
     * @expectedExceptionMessage A gateway must be specified.
     */
    public function it_throws_if_no_driver_specified()
    {
        new PayMe([]);
    }

    /**
     * @expectedException InvalidArgumentException
     *
     * @expectedExceptionMessage A gateway must be specified.
     */
    public function it_throws_if_no_driver_specified_on_make()
    {
        PayMe::make([]);
    }

    /**
     * @test
     *
     * @expectedException InvalidArgumentException
     *
     * @expectedExceptionMessage Unsupported gateway [foo].
     */
    public function it_thorws_on_not_gateway_supported()
    {
        PayMe::make(['driver' => 'foo']);
    }

    /**
     * @test
     *
     * @expectedException InvalidArgumentException
     *
     * @expectedExceptionMessage Unsupported gateway [bar].
     */
    public function it_thorws_on_not_factory_specified()
    {
        PayMe::make(['driver' => 'bar']);
    }

    /** @test */
    public function it_can_create_a_new_payme_instance()
    {
        $gateway = PayMe::make(['driver' => 'bogus']);

        $this->assertInstanceOf('Shoperti\PayMe\PayMe', $gateway);
    }

    /** @test */
    public function it_can_create_a_multiple_payme_instances()
    {
        $gatewayA = PayMe::make(['driver' => 'bogus']);
        $gatewayB = PayMe::make(['driver' => 'bogus']);

        $this->assertNotSame('Shoperti\PayMe\PayMe', $gatewayA);
        $this->assertNotSame('Shoperti\PayMe\PayMe', $gatewayB);
    }

    /** @test */
    public function it_can_create_a_multiple_payme_instances_with_different_config()
    {
        $gatewayA = PayMe::make(['driver' => 'bogus', 'foo' => 'bar']);
        $gatewayB = PayMe::make(['driver' => 'bogus', 'bar' => 'foo']);

        $this->assertNotSame($gatewayA->getConfig(), $gatewayB->getConfig());
    }

    /** @test */
    public function it_can_create_a_new_gateway_instance()
    {
        $gateway = PayMe::make(['driver' => 'bogus']);

        $this->assertInstanceOf('Shoperti\PayMe\Gateways\Bogus\BogusGateway', $gateway->getGateway());
    }

    /** @test */
    public function it_can_call_an_api_class()
    {
        $gateway = PayMe::make(['driver' => 'bogus']);

        $this->assertInstanceOf('Shoperti\PayMe\Gateways\Bogus\Charges', $gateway->charges());
    }

    /** @test */
    public function it_can_call_an_api_class_on_multiple_gateways()
    {
        $gatewayA = PayMe::make(['driver' => 'bogus']);
        $gatewayB = PayMe::make(['driver' => 'bogus']);

        $this->assertInstanceOf('Shoperti\PayMe\Gateways\Bogus\Charges', $gatewayA->charges());
        $this->assertInstanceOf('Shoperti\PayMe\Gateways\Bogus\Events', $gatewayB->events());
    }

    /**
     * @test
     *
     * @expectedException BadMethodCallException
     *
     * @expectedExceptionMessage Undefined method [foo] called.
     */
    public function it_throws_on_unsupported_method_for_gateway()
    {
        $gateway = PayMe::make(['driver' => 'bogus']);

        $gateway->foo();
    }

    /** @test */
    public function it_can_call_an_api_class_method()
    {
        $gateway = PayMe::make(['driver' => 'bogus']);

        $this->assertInstanceOf('Shoperti\PayMe\Gateways\Bogus\Charges', $gateway->charges());
        $this->assertInstanceOf('Shoperti\PayMe\Contracts\ResponseInterface', $gateway->charges()->create('foo', 'bar'));
    }
}
