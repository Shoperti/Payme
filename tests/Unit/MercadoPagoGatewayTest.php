<?php

namespace Shoperti\Tests\PayMe\Unit;

use Shoperti\PayMe\Gateways\MercadoPago\MercadoPagoGateway;

class MercadoPagoGatewayTest extends AbstractTestCase
{
    private $gateway = null;

    public function setUp()
    {
        parent::setUp();
        $this->gateway = new MercadoPagoTestGateway($this->credentials['mercadopago']);
    }

    /** @test */
    public function it_should_parse_an_approved_payment()
    {
        $response = $this->gateway->generateResponseFromRawResponse($this->getApprovedPayment());

        $this->assertTrue($response->success());
        $this->assertSame('paid', (string) $response->status());
        $this->assertSame('Transaction approved', $response->message());
    }

    /** @test */
    public function it_should_parse_an_authorized_payment()
    {
        $response = $this->gateway->generateResponseFromRawResponse($this->getAuthorizedPayment());

        $this->assertTrue($response->success());
        $this->assertSame('authorized', (string) $response->status());
        $this->assertSame('Transaction approved', $response->message());
    }

    /** @test */
    public function it_should_parse_a_pending_payment()
    {
        $response = $this->gateway->generateResponseFromRawResponse($this->getPendingPayment());

        $this->assertTrue($response->success());
        $this->assertSame('pending', (string) $response->status());
        $this->assertSame('Transaction approved', $response->message());
    }

    /** @test */
    public function it_should_parse_an_in_process_payment()
    {
        $response = $this->gateway->generateResponseFromRawResponse($this->getInProcessPayment());

        $this->assertTrue($response->success());
        $this->assertSame('pending', (string) $response->status());
        $this->assertSame('In process', $response->message());
    }

    /** @test */
    public function it_should_parse_an_in_meditation_payment()
    {
        $response = $this->gateway->generateResponseFromRawResponse($this->getInMeditationPayment());

        $this->assertTrue($response->success());
        $this->assertSame('pending', (string) $response->status());
        $this->assertSame('In meditation', $response->message());
    }

    /** @test */
    public function it_should_parse_a_rejected_payment()
    {
        $response = $this->gateway->generateResponseFromRawResponse($this->getRejectedPayment());

        $this->assertFalse($response->success());
        $this->assertSame('declined', (string) $response->status());
        $this->assertSame('You must authorize card issuer to pay the amount to MercadoPago', $response->message());
    }

    /** @test */
    public function it_should_parse_a_cancelled_payment()
    {
        $response = $this->gateway->generateResponseFromRawResponse($this->getCancelledPayment());

        $this->assertFalse($response->success());
        $this->assertSame('canceled', (string) $response->status());
        $this->assertSame('Cancelled', $response->message());
    }

    /** @test */
    public function it_should_parse_a_refunded_payment()
    {
        $response = $this->gateway->generateResponseFromRawResponse($this->getRefundedPayment());

        $this->assertTrue($response->success());
        $this->assertSame('refunded', (string) $response->status());
        $this->assertSame('Refunded', $response->message());
    }

    /** @test */
    public function it_should_parse_a_charged_back_payment()
    {
        $response = $this->gateway->generateResponseFromRawResponse($this->getChargedBackPayment());

        $this->assertTrue($response->success());
        $this->assertSame('charged_back', (string) $response->status());
        $this->assertSame('Charged back', $response->message());
    }

    /** @test */
    public function it_should_parse_an_empty_payment()
    {
        $response = $this->gateway->generateResponseFromRawResponse($this->getEmptyPayment());

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

    private function getRejectedPayment()
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

class MercadoPagoTestGateway extends MercadoPagoGateway
{
    public function generateResponseFromRawResponse($response)
    {
        return $this->respond($response, 200);
    }
}
