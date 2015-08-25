<?php

namespace Shoperti\Test\PayMe;

use Shoperti\PayMe\PayMeFactory;

class FactoryTest extends \PHPUnit_Framework_TestCase
{
    protected $factory;

    protected function setUp()
    {
        $this->factory = new PayMeFactory();
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage A gateway must be specified.
     */
    public function testNoDriverSpecified()
    {
        $this->factory->make([]);
    }
    /**
     * @test
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Unsupported factory [foo].
     */
    public function it_thorws_on_not_gateway_supported()
    {
        $this->factory->make(['gateway' => 'foo']);
    }
    /**
     * @test
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Unsupported factory [bar].
     */
    public function it_thorws_on_not_factory_specified()
    {
        $this->factory->factory(['gateway' => 'bar']);
    }

    /** @test */
    public function it_can_create_a_new_factory_instance()
    {
        $gateway = $this->factory->make(['gateway' => 'bogus']);

        $this->assertInstanceOf('Shoperti\PayMe\Gateways\Bogus', $gateway);
    }
}
