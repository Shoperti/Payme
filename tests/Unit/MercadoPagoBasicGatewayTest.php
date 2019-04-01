<?php

namespace Shoperti\Tests\PayMe\Unit;

use Shoperti\PayMe\Gateways\MercadoPagoBasic\MercadoPagoBasicGateway;

class MercadoPagoBasicGatewayTest extends AbstractTestCase
{
    /** @var MercadoPagoBasicTestGateway */
    private $gateway = null;

    public function setUp()
    {
        parent::setUp();
        $this->gateway = new MercadoPagoBasicTestGateway($this->credentials['mercadopago_basic']);
    }

    /** @test */
    public function it_should_parse_an_approved_payment()
    {
        $response = $this->gateway->getParsedResponse($this->getApprovedPayment());

        $this->assertTrue($response->success());
        $this->assertEquals('paid', $response->status());
        $this->assertSame('Transaction approved', $response->message());
    }

    /** @test */
    public function it_should_parse_a_pending_payment()
    {
        $response = $this->gateway->getParsedResponse($this->getPendingPayment());

        $this->assertTrue($response->success());
        $this->assertEquals('pending', $response->status());
        $this->assertSame('Transaction approved', $response->message());
    }

    /** @test */
    public function it_should_parse_an_in_process_payment()
    {
        $response = $this->gateway->getParsedResponse($this->getInProcessPayment());

        $this->assertTrue($response->success());
        $this->assertEquals('pending', $response->status());
        $this->assertSame('Transaction approved', $response->message());
    }

    /** @test */
    public function it_should_parse_a_rejected_payment()
    {
        $response = $this->gateway->getParsedResponse($this->getRejectedPayment());

        $this->assertFalse($response->success());
        $this->assertEquals('failed', $response->status());
        $this->assertSame('rejected', $response->message());
    }

    /** @test */
    public function it_should_parse_a_cancelled_payment()
    {
        $response = $this->gateway->getParsedResponse($this->getCancelledPayment());

        $this->assertFalse($response->success());
        $this->assertEquals('failed', $response->status());
        $this->assertSame('cancelled', $response->message());
    }

    /** @test */
    public function it_should_parse_an_empty_payment()
    {
        $response = $this->gateway->getParsedResponse($this->getEmptyPayment());

        $this->assertTrue($response->success());
        $this->assertEquals('pending', $response->status());
        $this->assertSame('Transaction approved', $response->message());
    }

    private function getApprovedPayment()
    {
        return [
            'id'         => 987654321,
            'status'     => 'closed',
            'site_id'    => 'MLM',
            'sponsor_id' => 268939999,
            'payments'   => [
                [
                    'id'                 => 4444444444,
                    'transaction_amount' => 4725,
                    'total_paid_amount'  => 4725,
                    'shipping_cost'      => 0,
                    'currency_id'        => 'MXN',
                    'status'             => 'approved',
                    'status_detail'      => 'accredited',
                    'operation_type'     => 'regular_payment',
                    'date_approved'      => '2019-02-14T19:36:07.000-04:00',
                    'date_created'       => '2019-02-14T19:36:04.000-04:00',
                    'last_modified'      => '2019-02-14T19:36:07.000-04:00',
                    'amount_refunded'    => 0,
                ],
            ],
            'paid_amount'        => 0,
            'refunded_amount'    => 0,
            'shipping_cost'      => 0,
            'cancelled'          => false,
            'external_reference' => 'ord_1',
            'additional_info'    => null,
            'notification_url'   => 'https://shopname.com/callback/gtw_1',
            'total_amount'       => 4725,
        ];
    }

    private function getPendingPayment()
    {
        return [
            'id'         => 958693479,
            'status'     => 'closed',
            'site_id'    => 'MLM',
            'sponsor_id' => 268939658,
            'payments'   => [
                [
                    'id'                 => 4479863061,
                    'transaction_amount' => 180,
                    'total_paid_amount'  => 180,
                    'shipping_cost'      => 0,
                    'currency_id'        => 'MXN',
                    'status'             => 'pending',
                    'status_detail'      => 'pending_waiting_payment',
                    'operation_type'     => 'regular_payment',
                    'date_approved'      => null,
                    'date_created'       => '2019-02-01T00:26:19.000-04:00',
                    'last_modified'      => '2019-02-01T00:26:19.000-04:00',
                    'amount_refunded'    => 0,
                ],
            ],
            'paid_amount'        => 0,
            'refunded_amount'    => 0,
            'shipping_cost'      => 0,
            'cancelled'          => false,
            'external_reference' => 'ord_2',
            'additional_info'    => null,
            'notification_url'   => 'https://shopname.com/callback/gtw_2',
            'total_amount'       => 180,
        ];
    }

    private function getInProcessPayment()
    {
        return [
            'id'         => 987654321,
            'status'     => 'closed',
            'site_id'    => 'MLM',
            'sponsor_id' => 268939999,
            'payments'   => [
                [
                    'id'                 => 4569892341,
                    'transaction_amount' => 1234,
                    'total_paid_amount'  => 1234,
                    'shipping_cost'      => 0,
                    'currency_id'        => 'MXN',
                    'status'             => 'in_process',
                    'status_detail'      => 'pending_review_manual',
                    'operation_type'     => 'regular_payment',
                    'date_approved'      => null,
                    'date_created'       => '2019-03-07T01:59:42.000-04:00',
                    'last_modified'      => '2019-03-07T01:59:42.000-04:00',
                    'amount_refunded'    => 0,
                ],
            ],
            'paid_amount'        => 0,
            'refunded_amount'    => 0,
            'shipping_cost'      => 0,
            'cancelled'          => false,
            'external_reference' => 'ord_3',
            'additional_info'    => null,
            'notification_url'   => 'https://shopname.com/callback/gtw_3',
            'total_amount'       => 1234,
        ];
    }

    private function getRejectedPayment()
    {
        return [
            'id'         => 987654321,
            'status'     => 'closed',
            'site_id'    => 'MLM',
            'sponsor_id' => 268939999,
            'payments'   => [
                [
                    'id'                 => 3620198092,
                    'transaction_amount' => 1148,
                    'total_paid_amount'  => 1148,
                    'shipping_cost'      => 0,
                    'currency_id'        => 'MXN',
                    'status'             => 'rejected',
                    'status_detail'      => 'cc_rejected_call_for_authorize',
                    'operation_type'     => 'regular_payment',
                    'date_approved'      => null,
                    'date_created'       => '2018-04-11T18:57:18.000-04:00',
                    'last_modified'      => '2018-04-11T18:57:20.000-04:00',
                    'amount_refunded'    => 0,
                ],
            ],
            'paid_amount'        => 0,
            'refunded_amount'    => 0,
            'shipping_cost'      => 0,
            'cancelled'          => false,
            'external_reference' => 'ord_4',
            'additional_info'    => null,
            'notification_url'   => 'https://shopname.com/callback/gtw_4',
            'total_amount'       => 1148,
        ];
    }

    private function getCancelledPayment()
    {
        return [
            'id'             => 958693479,
            'status'         => 'closed',
            'site_id'        => 'MLM',
            'sponsor_id'     => 268939658,
            'payments'       => [
                [
                    'id'                 => 4479863061,
                    'transaction_amount' => 180,
                    'total_paid_amount'  => 180,
                    'shipping_cost'      => 0,
                    'currency_id'        => 'MXN',
                    'status'             => 'cancelled',
                    'status_detail'      => 'expired',
                    'operation_type'     => 'regular_payment',
                    'date_approved'      => null,
                    'date_created'       => '2019-02-01T00:26:19.000-04:00',
                    'last_modified'      => '2019-03-03T00:30:08.000-04:00',
                    'amount_refunded'    => 0,
                ],
            ],
            'paid_amount'        => 0,
            'refunded_amount'    => 0,
            'shipping_cost'      => 0,
            'cancelled'          => false,
            'external_reference' => 'ord_5',
            'additional_info'    => null,
            'notification_url'   => 'https://shopname.com/callback/gtw_5',
            'total_amount'       => 180,
        ];
    }

    private function getEmptyPayment()
    {
        return [
            'id'                 => 984963276,
            'status'             => 'opened',
            'site_id'            => 'MLM',
            'sponsor_id'         => null,
            'payments'           => [],
            'paid_amount'        => 0,
            'refunded_amount'    => 0,
            'shipping_cost'      => 0,
            'cancelled'          => false,
            'external_reference' => 'ord_6',
            'additional_info'    => null,
            'notification_url'   => 'https://shopname.com/callback/gtw_6',
            'total_amount'       => 200,
        ];
    }
}

class MercadoPagoBasicTestGateway extends MercadoPagoBasicGateway
{
    public function getParsedResponse($response)
    {
        $response['isRedirect'] = false;
        $response['topic'] = '';

        return $this->respond($response, 200);
    }
}
