<?php

namespace Shoperti\Tests\PayMe\Functional;

use Shoperti\PayMe\PayMe;

abstract class AbstractFunctionalTestCase extends \PHPUnit_Framework_TestCase
{
    protected $gatewayData = [
        'gateway'    => 'FQN of the gateway class',
        'config'     => 'config key for credentials',
        'isRedirect' => 'optional boolean value indicating if payment creation leads to redirection',
    ];

    /** @test */
    public function it_should_create_a_new_gateway()
    {
        $sections = explode('\\', $this->gatewayData['gateway']);
        $sections[count($sections) - 1] = 'Charges';
        $chargesClass = implode('\\', $sections);

        $payMe = $this->getPayMe();

        $this->assertInstanceOf($this->gatewayData['gateway'], $payMe->getGateway());
        $this->assertInstanceOf($chargesClass, $payMe->charges());
    }

    // --- Testing helpers

    /**
     * @return \Shoperti\PayMe\Contracts\ResponseInterface $response
     */
    protected function successfulChargeRequest($token, $amount = null, $payload = [])
    {
        list($amount, $payload) = $this->fixOrderData($amount, $payload);

        $isRedirect = array_key_exists('isRedirect', $this->gatewayData)
            ? $this->gatewayData['isRedirect']
            : false;

        $charge = $this->chargeRequest($token, $amount, $payload);

        $this->assertSame($isRedirect, $charge->isRedirect());

        $this->assertSame(!$isRedirect, $charge->success());

        return $charge;
    }

    /**
     * This method automates a charge generation.
     *
     * if amount / payload params are null, they will be replaced by the order data stub.
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface $response
     */
    protected function chargeRequest($token, $amount = null, $payload = [])
    {
        list($amount, $payload) = $this->fixOrderData($amount, $payload);

        return $this->getPayMe()->charges()->create($amount, $token, $payload);
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

    protected function getOrderData(array $customData = [])
    {
        $order = include __DIR__.'/stubs/orderData.php';

        $order['payload'] = array_merge($order['payload'], $customData);

        return $order;
    }

    private function fixOrderData($amount, $payload)
    {
        $order = !is_null($amount) || is_array($payload) ? $this->getOrderData($payload ?: []) : null;

        return[
            !is_null($amount) ? $amount : $order['total'],
            is_array($payload) ? $order['payload'] : [],
        ];
    }
}
