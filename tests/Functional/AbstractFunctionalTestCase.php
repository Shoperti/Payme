<?php

namespace Shoperti\Tests\PayMe\Functional;

use Shoperti\PayMe\PayMe;

abstract class AbstractFunctionalTestCase extends \PHPUnit_Framework_TestCase
{
    protected $credentials;

    public function setUp()
    {
        $this->credentials = require dirname(__DIR__).'/stubs/credentials.php';
    }

    protected function getPayMe(array $overrides = [])
    {
        return PayMe::make(array_merge($this->credentials[$this->gatewayKey], $overrides));
    }

    protected function getOrderPayload(array $customData = [])
    {
        $order = include __DIR__.'/stubs/orderPayload.php';

        $order['payload'] = array_merge($order['payload'], $customData);

        return $order;
    }
}
