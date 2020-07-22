<?php

namespace Shoperti\Tests\PayMe\Functional;

use Shoperti\PayMe\PayMe;

abstract class AbstractFunctionalTestCase extends \PHPUnit_Framework_TestCase
{
    protected $gatewayData = [
        'gateway' => 'FQN of the gateway class',
        'charges' => 'FQN of the gateway charges class',
        'config'  => 'config key for credentials',
    ];

    /** @test */
    public function it_should_create_a_new_gateway()
    {
        $payMe = $this->getPayMe();

        $this->assertInstanceOf($this->gatewayData['gateway'], $payMe->getGateway());
        $this->assertInstanceOf($this->gatewayData['charges'], $payMe->charges());
    }

    protected function getPayMe(array $overrides = [])
    {
        return PayMe::make(array_merge($this->getCredentials(), $overrides));
    }

    protected function getCredentials()
    {
        $credentials = require dirname(__DIR__).'/stubs/credentials.php';

        return $credentials[$this->gatewayData['config']];
    }

    protected function getOrderPayload(array $customData = [])
    {
        $order = include __DIR__.'/stubs/orderPayload.php';

        $order['payload'] = array_merge($order['payload'], $customData);

        return $order;
    }
}
