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
        $this->gateway = $this->makeGateway(
            $this->gatewayData['class'],
            $this->gatewayData['config'],
            isset($this->gatewayData['innerMethod']) ? $this->gatewayData['innerMethod'] : null,
            isset($this->gatewayData['innerMethodExtraParams']) ? $this->gatewayData['innerMethodExtraParams'] : []
        );
    }

    // Test templates

    protected function approvedPaymentTest($payload, $message = 'Transaction approved')
    {
        $response = $this->gateway->generateResponseFromRawResponse($payload);

        $this->assertTrue($response->success());
        $this->assertSame('paid', (string) $response->status());
        $this->assertSame($message, $response->message());
    }

    protected function declinedPaymentTest($payload, $message = 'rejected')
    {
        $response = $this->gateway->generateResponseFromRawResponse($payload);

        $this->assertFalse($response->success());
        $this->assertSame('declined', (string) $response->status());
        $this->assertSame($message, $response->message());
    }

    protected function pendingPaymentTest($payload, $message = 'Transaction approved')
    {
        $response = $this->gateway->generateResponseFromRawResponse($payload);

        $this->assertTrue($response->success());
        $this->assertSame('pending', (string) $response->status());
        $this->assertSame($message, $response->message());
    }

    protected function activePaymentTest($payload)
    {
        $response = $this->gateway->generateResponseFromRawResponse($payload);

        $this->assertTrue($response->success());
        $this->assertSame('active', (string) $response->status());
        $this->assertSame('Transaction approved', $response->message());
    }

    protected function authorizedPaymentTest($payload)
    {
        $response = $this->gateway->generateResponseFromRawResponse($payload);

        $this->assertTrue($response->success());
        $this->assertSame('authorized', (string) $response->status());
        $this->assertSame('Transaction approved', $response->message());
    }

    protected function approvedRefundTest($payload, $message = 'Refunded')
    {
        $response = $this->gateway->generateResponseFromRawResponse($payload);

        $this->assertTrue($response->success());
        $this->assertSame('refunded', (string) $response->status());
        $this->assertSame($message, $response->message());
    }

    protected function approvedPartialRefundTest($payload, $message = 'Transaction approved')
    {
        $response = $this->gateway->generateResponseFromRawResponse($payload);

        $this->assertTrue($response->success());
        $this->assertSame('partially_refunded', (string) $response->status());
        $this->assertSame($message, $response->message());
    }

    protected function canceledPaymentTest($payload, $message = 'rejected')
    {
        $response = $this->gateway->generateResponseFromRawResponse($payload);

        $this->assertFalse($response->success());
        $this->assertSame('canceled', (string) $response->status());
        $this->assertSame($message, $response->message());
    }

    protected function chargeBackTest($payload)
    {
        $response = $this->gateway->generateResponseFromRawResponse($payload);

        $this->assertTrue($response->success());
        $this->assertSame('charged_back', (string) $response->status());
        $this->assertSame('Charged back', $response->message());
    }

    protected function failedTPaymentTest($payload, $message = '')
    {
        $response = $this->gateway->generateResponseFromRawResponse($payload);

        $this->assertFalse($response->success());
        $this->assertSame('failed', (string) $response->status());
        $this->assertSame($message, $response->message());
    }

    /**
     * Returns an subclass instance of the given gateway.
     *
     * The returned object will contain the public method `generateResponseFromRawResponse` to be used
     * to call the inner method that parses and returns the gateway response.
     *
     * @param string $gatewayClass
     * @param string $config
     * @param string $innerMethod
     * @param array  $innerMethodExtraParams
     *
     * @return \Shoperti\PayMe\Gateways\Conekta\AbstractGateway
     */
    private function makeGateway($gatewayClass, $config, $innerMethod = null, $innerMethodExtraParams = [])
    {
        $innerMethod = $innerMethod ?: 'respond';

        $params = implode(',', array_merge(['$response'], $innerMethodExtraParams));

        $credentials = require dirname(__DIR__).'/stubs/credentials.php';

        $instance = null;

        eval('
        $instance = new class($credentials["'.$config.'"]) extends '.$gatewayClass.'
        {
            public $preprocessPayload = null;

            public function generateResponseFromRawResponse($response)
            {
                // this allows us to manipulate the payload before sending it to the parse method
                if ($this->preprocessPayload) {
                    $response = ($this->preprocessPayload)($response);
                }

                return $this->'.$innerMethod.'('.$params.');
            }
        };');

        return $instance;
    }
}
