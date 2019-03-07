<?php

namespace Shoperti\Tests\PayMe\Unit;

use Shoperti\PayMe\Gateways\MercadoPagoBasic\MercadoPagoBasicGateway;

class MercadoPagoBasicGatewayTest extends AbstractTestCase
{
    private $gateway = null;

    public function setUp()
    {
        parent::setUp();
        $this->gateway = new MercadoPagoBasicTestGateway($this->credentials['mercadopago_basic']);
    }

    /** @test */
    public function it_should_parse_an_approved_payment()
    {

        $response = $this->gateway->generateResponseFromRawResponse($this->getApprovedPayment());

        $this->assertTrue($response->success());
        $this->assertSame('Transaction approved', $response->message());
    }

    /** @test */
    public function it_should_parse_a_rejected_payment()
    {
        $response = $this->gateway->generateResponseFromRawResponse($this->getRejectedPayment());

        $this->assertFalse($response->success());
        $this->assertSame('rejected', $response->message());
    }

    private function getApprovedPayment()
    {
        return [
            'id'             => 987654321,
            'preference_id'  => '273780401-d046e535-4542-403a-af7d-111111111111',
            'date_created'   => '2019-02-01T00:26:18.000-04:00',
            'last_updated'   => '2019-03-03T00:30:08.000-04:00',
            'application_id' => null,
            'status'         => 'closed',
            'site_id'        => 'MLM',
            'payer'          => ['id' => 234567890, 'email' => 'payer@gmail.com'],
            'collector'      => ['id' => 123456789, 'email' => 'seller@mail.com', 'nickname' => 'SELLERML'],
            'sponsor_id'     => 268939999,
            'payments'       => [
                [
                    'id'                 => 4444444444,
                    'transaction_amount' => 180,
                    'total_paid_amount'  => 180,
                    'shipping_cost'      => 0,
                    'currency_id'        => 'MXN',
                    'status'             => 'approved',
                    'status_detail'      => 'expired',
                    'operation_type'     => 'regular_payment',
                    'date_approved'      => null,
                    'date_created'       => '2019-02-01T00:26:19.000-04:00',
                    'last_modified'      => '2019-03-03T00:30:08.000-04:00',
                    'amount_refunded'    => 0,
                ],
            ],
            'paid_amount'     => 0,
            'refunded_amount' => 0,
            'shipping_cost'   => 0,
            'cancelled'       => false,
            'items'           => [
                [
                    'category_id' => null,
                    'currency_id' => 'MXN',
                    'description' => 'ShopName',
                    'id'          => 'ord_1',
                    'picture_url' => null,
                    'quantity'    => 1,
                    'unit_price'  => 180,
                    'title'       => 'ShopName',
                ],
            ],
            'marketplace'        => 'NONE',
            'shipments'          => [],
            'external_reference' => 'ord_1',
            'additional_info'    => null,
            'notification_url'   => 'https://shopname.com/callback/gtw_1',
            'total_amount'       => 180,
        ];
    }

    private function getRejectedPayment()
    {
        return [
            'id'             => 987654321,
            'preference_id'  => '273780401-d046e535-4542-403a-af7d-111111111111',
            'date_created'   => '2019-02-01T00:26:18.000-04:00',
            'last_updated'   => '2019-03-03T00:30:08.000-04:00',
            'application_id' => null,
            'status'         => 'closed',
            'site_id'        => 'MLM',
            'payer'          => ['id' => 234567890, 'email' => 'payer@gmail.com'],
            'collector'      => ['id' => 123456789, 'email' => 'seller@mail.com', 'nickname' => 'SELLERML'],
            'sponsor_id'     => 268939999,
            'payments'       => [
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
            'paid_amount'     => 0,
            'refunded_amount' => 0,
            'shipping_cost'   => 0,
            'cancelled'       => false,
            'items'           => [
                [
                    'category_id' => null,
                    'currency_id' => 'MXN',
                    'description' => 'ShopName',
                    'id'          => 'ord_1',
                    'picture_url' => null,
                    'quantity'    => 1,
                    'unit_price'  => 180,
                    'title'       => 'ShopName',
                ],
            ],
            'marketplace'        => 'NONE',
            'shipments'          => [],
            'external_reference' => 'ord_1',
            'additional_info'    => null,
            'notification_url'   => 'https://shopname.com/callback/gtw_1',
            'total_amount'       => 180,
        ];
    }
}

class MercadoPagoBasicTestGateway extends MercadoPagoBasicGateway
{
    public function generateResponseFromRawResponse($response)
    {
        $response['isRedirect'] = false;
        $response['topic'] = '';

        return $this->respond($response, 200);
    }
}
