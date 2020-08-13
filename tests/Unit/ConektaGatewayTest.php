<?php

namespace Shoperti\Tests\PayMe\Unit;

use Shoperti\PayMe\Gateways\Conekta\ConektaGateway;

class ConektaGatewayTest extends AbstractTestCase
{
    protected $gatewayData = [
        'class'  => ConektaGateway::class,
        'config' => 'conekta',
    ];

    /**
     * @test
     * charges()->create()
     */
    public function it_should_parse_an_approved_payment()
    {
        $this->approvedPaymentTest($this->getApprovedPayment());
    }

    /**
     * @test
     * events()->get()
     */
    public function it_should_parse_an_expired_payment()
    {
        $this->expiredPaymentTest($this->getExpiredPaymentResponse());
    }

    /**
     * @test
     * events()->get()
     */
    public function it_should_parse_an_expired_order()
    {
        $this->expiredPaymentTest($this->getExpiredOrderResponse());
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
                    'id'             => '111111111111111111111111',
                    'customer_id'    => null,
                    'order_id'       => 'ord_2kSGL1fWJuLWE5KRY',
                    'payment_method' => [
                        'service_name' => 'OxxoPay',
                        'barcode_url'  => 'https://s3.amazonaws.com/cash_payment_barcodes/90000000000000.png',
                        'store'        => '10AAA00000',
                        'auth_code'    => 11000000,
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
                    'id'                        => 'webhl_00000000000000000',
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

    private function getExpiredPaymentResponse()
    {
        return [
            'data' => [
                'object' => [
                    'id'             => '222222222222222222222222',
                    'livemode'       => true,
                    'created_at'     => 1596996917,
                    'currency'       => 'MXN',
                    'payment_method' => [
                        'service_name' => 'OxxoPay',
                        'barcode_url'  => 'https://s3.amazonaws.com/cash_payment_barcodes/93000481878641.png',
                        'object'       => 'cash_payment',
                        'type'         => 'oxxo',
                        'expires_at'   => 1597169717,
                        'store_name'   => 'OXXO',
                        'reference'    => '99999999999999',
                    ],
                    'object'      => 'charge',
                    'description' => 'Payment from order',
                    'status'      => 'expired',
                    'amount'      => 59900,
                    'fee'         => 2432,
                    'customer_id' => null,
                    'order_id'    => 'ord_00000000000000001',
                ],
                'previous_attributes' => [
                    'status' => 'pending_payment',
                ],
            ],
            'livemode'       => true,
            'webhook_status' => 'pending',
            'webhook_logs'   => [
                [
                    'id'                        => 'webhl_22222222222222222',
                    'url'                       => 'https://domain.example.com/hooks/incoming/gateways/gtw_aaaaaaaaaaaaaaaaaaaaaaaaa',
                    'failed_attempts'           => 0,
                    'last_http_response_status' => -1,
                    'object'                    => 'webhook_log',
                    'last_attempted_at'         => 0,
                ],
            ],
            'id'         => '555555555555555555555555',
            'object'     => 'event',
            'type'       => 'charge.expired',
            'created_at' => 1597186280,
        ];
    }

    private function getExpiredOrderResponse()
    {
        return [
            'data' => [
                'object' => [
                    'livemode'        => true,
                    'amount'          => 59900,
                    'currency'        => 'MXN',
                    'payment_status'  => 'expired',
                    'amount_refunded' => 0,
                    'customer_info'   => [
                        'email'  => 'custmer@example.com',
                        'phone'  => '1234567890',
                        'name'   => 'John Doe',
                        'object' => 'customer_info',
                    ],
                    'shipping_contact' => [
                        'receiver' => 'John Doe',
                        'phone'    => '1234567890',
                        'address'  => [
                            'street1'     => 'Fake Address',
                            'street2'     => '123',
                            'city'        => 'City',
                            'state'       => 'State',
                            'country'     => 'mx',
                            'residential' => true,
                            'object'      => 'shipping_address',
                            'postal_code' => '80000',
                        ],
                        'id'         => 'ship_cont_22222222222222222',
                        'object'     => 'shipping_contact',
                        'created_at' => 0,
                    ],
                    'object'   => 'order',
                    'id'       => 'ord_22222222222222222',
                    'metadata' => [
                        'reference' => 'payme_order_0000000000000000000000001',
                    ],
                    'created_at' => 1596996917,
                    'updated_at' => 1597186281,
                    'line_items' => [
                        'object'   => 'list',
                        'has_more' => false,
                        'total'    => 1,
                        'data'     => [
                            [
                                'name'        => 'Product Name',
                                'description' => 'Product Description',
                                'unit_price'  => 59900,
                                'quantity'    => 1,
                                'sku'         => '-',
                                'tags'        => [
                                    'none',
                                ],
                                'object'         => 'line_item',
                                'id'             => 'line_item_22222222222222222',
                                'parent_id'      => 'ord_22222222222222222',
                                'metadata'       => [],
                                'antifraud_info' => [],
                            ],
                        ],
                    ],
                    'shipping_lines' => [
                        'object'   => 'list',
                        'has_more' => false,
                        'total'    => 1,
                        'data'     => [
                            [
                                'amount'    => 0,
                                'carrier'   => 'shoperti/kometia',
                                'method'    => 'pending',
                                'object'    => 'shipping_line',
                                'id'        => 'ship_lin_22222222222222222',
                                'parent_id' => 'ord_22222222222222222',
                            ],
                        ],
                    ],
                    'discount_lines' => [
                        'object'   => 'list',
                        'has_more' => false,
                        'total'    => 1,
                        'data'     => [
                            [
                                'code'      => '---',
                                'amount'    => 0,
                                'type'      => 'coupon',
                                'object'    => 'discount_line',
                                'id'        => 'dis_lin_22222222222222222',
                                'parent_id' => 'ord_22222222222222222',
                            ],
                        ],
                    ],
                    'charges' => [
                        'object'   => 'list',
                        'has_more' => false,
                        'total'    => 1,
                        'data'     => [
                            [
                                'id'             => '555555555555555555555555',
                                'livemode'       => true,
                                'created_at'     => 1596996917,
                                'currency'       => 'MXN',
                                'payment_method' => [
                                    'service_name' => 'OxxoPay',
                                    'barcode_url'  => 'https://s3.amazonaws.com/cash_payment_barcodes/93000000000000.png',
                                    'object'       => 'cash_payment',
                                    'type'         => 'oxxo',
                                    'expires_at'   => 1597169717,
                                    'store_name'   => 'OXXO',
                                    'reference'    => '93000000000000',
                                ],
                                'object'      => 'charge',
                                'description' => 'Payment from order',
                                'status'      => 'expired',
                                'amount'      => 59900,
                                'fee'         => 2432,
                                'customer_id' => null,
                                'order_id'    => 'ord_22222222222222222',
                            ],
                        ],
                    ],
                ],
                'previous_attributes' => [],
            ],
            'livemode'       => true,
            'webhook_status' => 'pending',
            'webhook_logs'   => [
                [
                    'id'                        => 'webhl_22222222222222222',
                    'url'                       => 'https://domain.example.com/hooks/incoming/gateways/gtw_aaaaaaaaaaaaaaaaaaaaaaaaa',
                    'failed_attempts'           => 0,
                    'last_http_response_status' => -1,
                    'object'                    => 'webhook_log',
                    'last_attempted_at'         => 0,
                ],
            ],
            'id'         => 'ffffffffffffffffffffffff',
            'object'     => 'event',
            'type'       => 'order.expired',
            'created_at' => 1597186281,
        ];
    }
}
