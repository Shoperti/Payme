<?php

namespace Shoperti\Tests\PayMe\Unit;

use Shoperti\PayMe\Gateways\PaypalPlus\PaypalPlusGateway;

class PaypalPlusGatewayTest extends AbstractTestCase
{
    /** @var PaypalPlusTestGateway */
    private $gateway = null;

    public function setUp()
    {
        parent::setUp();
        $this->gateway = new PaypalPlusTestGateway($this->credentials['paypal_plus']);
    }

    /** @test */
    public function it_should_parse_an_approved_payment()
    {
        $response = $this->gateway->getParsedResponse($this->getApprovedPayment());

        $this->assertTrue($response->success());
        $this->assertSame('paid', (string) $response->status());
        $this->assertSame('Transaction approved', $response->message());
    }

    /** @test */
    public function it_should_parse_a_pending_payment()
    {
        $response = $this->gateway->getParsedResponse($this->getPendingPayment());

        $this->assertTrue($response->success());
        $this->assertSame('pending', (string) $response->status());
        $this->assertSame('Transaction approved', $response->message());
    }

    /** @test */
    public function it_should_parse_a_denied_payment()
    {
        $response = $this->gateway->getParsedResponse($this->getDeniedPayment());

        $this->assertFalse($response->success());
        $this->assertSame('declined', (string) $response->status());
    }

    /** @test */
    public function it_should_parse_a_refunded_payment()
    {
        $response = $this->gateway->getParsedResponse($this->getRefundedPayment());

        $this->assertTrue($response->success());
        $this->assertSame('refunded', (string) $response->status());
    }

    /** @test */
    public function it_should_parse_a_partially_refunded_payment()
    {
        $response = $this->gateway->getParsedResponse($this->getPartiallyRefundedPayment());

        $this->assertTrue($response->success());
        $this->assertSame('partially_refunded', (string) $response->status());
    }

    /**
     * @see https://developer.paypal.com/docs/api/payments/v1/#payment_execute
     * @see https://developer.paypal.com/docs/api/payments/v1/#definition-sale
    */
    private function getApprovedPayment()
    {
        return [
            'id'           => 'PAY-4D905294SK041703DLH32GIA',
            'intent'       => 'sale',
            'state'        => 'approved',                   // ignore
            'cart'         => '755335510M315821L',
            'transactions' => [
                [
                    'related_resources' => [
                        [
                            'sale' => [
                                'id'     => '60016985HM514502U',
                                'state'  => 'completed',            // the one we care about
                                'amount' => [
                                    'total'    => '522.00',
                                    'currency' => 'MXN',
                                    'details'  => [
                                        'subtotal' => '522.00',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function getPendingPayment()
    {
        $payload = $this->getApprovedPayment();
        $payload['transactions'][0]['related_resources'][0]['sale']['state'] = 'pending';

        return $payload;
    }

    private function getDeniedPayment()
    {
        $payload = $this->getApprovedPayment();
        $payload['transactions'][0]['related_resources'][0]['sale']['state'] = 'denied';

        return $payload;
    }

    private function getRefundedPayment()
    {
        $payload = $this->getApprovedPayment();
        $payload['transactions'][0]['related_resources'][0]['sale']['state'] = 'refunded';

        return $payload;
    }

    private function getPartiallyRefundedPayment()
    {
        $payload = $this->getApprovedPayment();
        $payload['transactions'][0]['related_resources'][0]['sale']['state'] = 'partially_refunded';

        return $payload;
    }
}

class PaypalPlusTestGateway extends PaypalPlusGateway
{
    public function getParsedResponse($response)
    {
        return $this->mapResponse($response, 200);
    }
}
