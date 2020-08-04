<?php

namespace Shoperti\Tests\PayMe\Functional;

use Shoperti\PayMe\PayMe;

abstract class AbstractFunctionalTestCase extends \PHPUnit_Framework_TestCase
{
    protected $gatewayData = [
        'config'     => 'config key for credentials',
        'isRedirect' => 'optional boolean value indicating if payment creation leads to redirection',
    ];

    protected function getPayMe(array $overrides = [])
    {
        return PayMe::make(array_merge($this->getCredentials(), $overrides));
    }

    protected function getCredentials($config = null)
    {
        $credentials = require dirname(__DIR__).'/stubs/credentials.php';

        return $credentials[$config ?: $this->gatewayData['config']];
    }

    protected function getOrderData(array $customData = [])
    {
        $order = include __DIR__.'/stubs/orderData.php';

        $order['payload'] = array_merge($order['payload'], $customData);

        return $order;
    }
}
