<?php

namespace Shoperti\Tests\PayMe\Unit;

abstract class AbstractTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * The gateway being tested.
     *
     * @var \Shoperti\PayMe\Gateways\Conekta\AbstractGateway
     */
    protected $gateway;

    public function setUp()
    {
        $this->gateway = $this->makeGateway();
    }

    // Test templates

    protected function approvedPaymentTest($payload, $message = 'Transaction approved')
    {
        $response = $this->parseResponse($payload);

        $this->assertTrue($response->success());
        $this->assertSame('paid', (string) $response->status());
        $this->assertSame($message, $response->message());
    }

    protected function declinedPaymentTest($payload, $message = 'rejected')
    {
        $response = $this->parseResponse($payload);

        $this->assertFalse($response->success());
        $this->assertSame('declined', (string) $response->status());
        $this->assertSame($message, $response->message());
    }

    protected function expiredPaymentTest($payload, $message = 'Transaction approved')
    {
        $response = $this->parseResponse($payload);

        $this->assertTrue($response->success());
        $this->assertSame('expired', (string) $response->status());
        $this->assertSame($message, $response->message());
    }

    /**
     * charges()->create()
     * Test for successful charge creation using a gateway that redirects.
     */
    protected function createSuccessfulChargeWithRedirectTest($payload, $message = 'Transaction approved')
    {
        $response = $this->parseResponse($payload);

        $this->assertFalse($response->success());
        $this->assertSame('pending', (string) $response->status());
        $this->assertSame($message, $response->message());
    }

    protected function pendingPaymentTest($payload, $message = 'Transaction approved')
    {
        $response = $this->parseResponse($payload);

        $this->assertTrue($response->success());
        $this->assertSame('pending', (string) $response->status());
        $this->assertSame($message, $response->message());
    }

    protected function failedTPaymentTest($payload, $message = '')
    {
        $response = $this->parseResponse($payload);

        $this->assertFalse($response->success());
        $this->assertSame('failed', (string) $response->status());
        $this->assertSame($message, $response->message());
    }

    protected function activePaymentTest($payload)
    {
        $response = $this->parseResponse($payload);

        $this->assertTrue($response->success());
        $this->assertSame('active', (string) $response->status());
        $this->assertSame('Transaction approved', $response->message());
    }

    protected function authorizedPaymentTest($payload)
    {
        $response = $this->parseResponse($payload);

        $this->assertTrue($response->success());
        $this->assertSame('authorized', (string) $response->status());
        $this->assertSame('Transaction approved', $response->message());
    }

    protected function approvedRefundTest($payload, $message = 'Refunded')
    {
        $response = $this->parseResponse($payload);

        $this->assertTrue($response->success());
        $this->assertSame('refunded', (string) $response->status());
        $this->assertSame($message, $response->message());
    }

    protected function approvedPartialRefundTest($payload, $message = 'Transaction approved')
    {
        $response = $this->parseResponse($payload);

        $this->assertTrue($response->success());
        $this->assertSame('partially_refunded', (string) $response->status());
        $this->assertSame($message, $response->message());
    }

    protected function canceledPaymentTest($payload, $message = 'rejected')
    {
        $response = $this->parseResponse($payload);

        $this->assertFalse($response->success());
        $this->assertSame('canceled', (string) $response->status());
        $this->assertSame($message, $response->message());
    }

    protected function chargeBackTest($payload)
    {
        $response = $this->parseResponse($payload);

        $this->assertTrue($response->success());
        $this->assertSame('charged_back', (string) $response->status());
        $this->assertSame('Charged back', $response->message());
    }

    private function makeGateway()
    {
        $gatewayClass = $this->gatewayData['class'];

        $credentials = require dirname(__DIR__).'/stubs/credentials.php';

        $config = $credentials[$this->gatewayData['config']];

        return new $gatewayClass($config);
    }

    protected function parseResponse($response)
    {
        $params = isset($this->gatewayData['params']) ? $this->gatewayData['params'] : null;

        $response = isset($this->gatewayData['preprocessPayload'])
            ? $this->gatewayData['preprocessPayload']($response)
            : $response;

        return $this->gateway->respond($response, $params);
    }
}
