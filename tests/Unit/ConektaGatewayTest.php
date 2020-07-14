<?php

namespace Shoperti\Tests\PayMe\Unit;

use Shoperti\PayMe\Gateways\Conekta\ConektaGateway;

class ConektaGatewayTest extends AbstractTestCase
{
    protected $gatewayData = [
        'class'  => ConektaGateway::class,
        'config' => 'conekta',
    ];

    /** @test */
    public function it_should_parse_an_approved_payment()
    {
        $this->approvedPaymentTest($this->getApprovedPayment());
    }

    private function getApprovedPayment()
    {
        return [
            'data' => [
                'previous_attributes' => [],
                'object'              => [
                    'amount'         => 233000,
                    'livemode'       => true,
                    'fee'            => 9460,
                    'created_at'     => 1554857326,
                    'description'    => 'Payment from order',
                    'paid_at'        => 1554924406,
                    'currency'       => 'MXN',
                    'id'             => '5cad3d6e9b3fa97bdaf65a41',
                    'customer_id'    => null,
                    'order_id'       => 'ord_2kSGL1fWJuLWE5KRY',
                    'payment_method' => [
                        'service_name' => 'OxxoPay',
                        'barcode_url'  => 'https://s3.amazonaws.com/cash_payment_barcodes/93000097488934.png',
                        'store'        => '10MEX50Y45',
                        'auth_code'    => 11041940,
                        'object'       => 'cash_payment',
                        'type'         => 'oxxo',
                        'expires_at'   => 1555390800,
                        'store_name'   => 'OXXO',
                        'reference'    => '93000097488934',
                    ],
                    'object'         => 'charge',
                    'status'         => 'paid',
                ],
            ],
            'livemode'       => true,
            'webhook_status' => 'pending',
            'webhook_logs'   => [
                [
                    'id'                        => 'webhl_2kSX3rDJXEpo37c3v',
                    'url'                       => 'http://52.53.178.225/modules/conektaprestashop/notification.php',
                    'failed_attempts'           => 0,
                    'last_http_response_status' => -1,
                    'object'                    => 'webhook_log',
                    'last_attempted_at'         => 0,
                ],
            ],
            'id'         => '5cae4376518e60732a6f44a4',
            'object'     => 'event',
            'type'       => 'charge.paid',
            'created_at' => 1554924406,
        ];
    }
}
