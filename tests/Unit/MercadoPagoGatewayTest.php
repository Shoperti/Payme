<?php

namespace Shoperti\Tests\PayMe\Unit;

use Shoperti\PayMe\Gateways\MercadoPago\MercadoPagoGateway;

class MercadoPagoGatewayTest extends AbstractTestCase
{
    protected $gatewayData = [
        'class'     => MercadoPagoGateway::class,
        'config'    => 'mercadopago',
        'moreParam' => 200,
    ];

    /** @test */
    public function it_should_parse_an_approved_payment()
    {
        $this->approvedPaymentTest($this->getApprovedPayment());
    }

    /** @test */
    public function it_should_parse_a_rejected_payment()
    {
        $this->declinedPaymentTest($this->getDeclinedPayment(), 'You must authorize card issuer to pay the amount to MercadoPago');
    }

    /** @test */
    public function it_should_parse_a_pending_payment()
    {
        $this->pendingPaymentTest($this->getPendingPayment());
        $this->pendingPaymentTest($this->getInProcessPayment(), 'In process');
        $this->pendingPaymentTest($this->getInMeditationPayment(), 'In meditation');
    }

    /** @test */
    public function it_should_parse_an_authorized_payment()
    {
        $this->authorizedPaymentTest($this->getAuthorizedPayment());
    }

    /** @test */
    public function it_should_parse_a_refunded_payment()
    {
        $this->approvedRefundTest($this->getRefundedPayment());
    }

    /** @test */
    public function it_should_parse_a_cancelled_payment()
    {
        $this->canceledPaymentTest($this->getCancelledPayment(), 'Cancelled');
    }

    /** @test */
    public function it_should_parse_a_charged_back_payment()
    {
        $this->chargeBackTest($this->getChargedBackPayment());
    }

    /** @test */
    public function it_should_parse_an_empty_payment()
    {
        $response = $this->parseResponse($this->getEmptyPayment());

        $this->assertFalse($response->success());
        $this->assertSame('pending', (string) $response->status());
    }

    private function getApprovedPayment()
    {
        return [
            'status'        => 'approved',
            'status_detail' => 'accredited',
            'live_mode'     => false,
        ];
    }

    private function getAuthorizedPayment()
    {
        return [
            'status'        => 'authorized',
            'status_detail' => 'accredited',
            'live_mode'     => false,
        ];
    }

    private function getPendingPayment()
    {
        return [
            'status'        => 'pending',
            'status_detail' => 'accredited',
            'live_mode'     => false,
        ];
    }

    private function getInProcessPayment()
    {
        return [
            'status'    => 'in_process',
            'live_mode' => false,
        ];
    }

    private function getInMeditationPayment()
    {
        return [
            'status'    => 'in_meditation',
            'live_mode' => false,
        ];
    }

    private function getDeclinedPayment()
    {
        return [
            'status'        => 'rejected',
            'status_detail' => 'cc_rejected_call_for_authorize',
            'live_mode'     => false,
        ];
    }

    private function getCancelledPayment()
    {
        return [
            'status'    => 'cancelled',
            'live_mode' => false,
        ];
    }

    private function getRefundedPayment()
    {
        return [
            'status'    => 'refunded',
            'live_mode' => false,
        ];
    }

    private function getChargedBackPayment()
    {
        return [
            'status'    => 'charged_back',
            'live_mode' => false,
        ];
    }

    private function getEmptyPayment()
    {
        return [
        ];
    }
}
