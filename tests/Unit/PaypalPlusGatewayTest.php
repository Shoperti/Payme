<?php

namespace Shoperti\Tests\PayMe\Unit;

use Shoperti\PayMe\Gateways\PaypalPlus\PaypalPlusGateway;

class PaypalPlusGatewayTest extends AbstractTestCase
{
    protected $gatewayData = [
        'class'     => PaypalPlusGateway::class,
        'config'    => 'paypal_plus',
        'moreParam' => ['request' => [], 'options' => [], 'statusCode' => 200],
    ];

    /**
     * @test
     * charges()->complete()
     */
    public function it_should_parse_an_approved_payment()
    {
        $this->approvedPaymentTest($this->getApprovedPayment());
    }

    /** @test */
    public function it_should_parse_a_denied_payment()
    {
        $this->declinedPaymentTest($this->getDeniedPayment(), '');
    }

    /** @test */
    public function it_should_parse_a_pending_payment()
    {
        $this->pendingPaymentTest($this->getPendingPayment());
    }

    /** @test */
    public function it_should_parse_a_refunded_payment()
    {
        $this->approvedRefundTest($this->getRefundedPayment(), 'Transaction approved');
    }

    /** @test */
    public function it_should_parse_a_partially_refunded_payment()
    {
        $this->approvedPartialRefundTest($this->getPartiallyRefundedPayment());
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
