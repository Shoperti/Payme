<?php

namespace Shoperti\Tests\PayMe\Unit;

use Shoperti\PayMe\Gateways\PaypalPlus\PaypalPlusGateway;

class PaypalPlusGatewayTest extends AbstractTestCase
{
    protected $gatewayData = [
        'class'  => PaypalPlusGateway::class,
        'config' => 'paypal_plus',
        'params' => ['request' => [], 'options' => [], 'statusCode' => 200],
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

    private function getPartiallyRefundedPayment()
    {
        $payload = $this->getApprovedPayment();
        $payload['transactions'][0]['related_resources'][0]['sale']['state'] = 'partially_refunded';

        return $payload;
    }

    private function getRefundedPayment()
    {
        return [
            'id'     => 'PAYID-L40000000000000000000000',
            'intent' => 'sale',
            'state'  => 'approved',
            'cart'   => '9J863377TH1000000',
            'payer'  => [
                'payment_method' => 'paypal',
                'status'         => 'UNVERIFIED',
                'payer_info'     => [
                    'email'            => 'payer@example.com',
                    'first_name'       => 'Juan',
                    'last_name'        => 'Tremendo',
                    'payer_id'         => '8333333333333',
                    'shipping_address' => [
                        'recipient_name' => 'juan tremendo',
                        'line1'          => 'av costera de las palmas 2000',
                        'line2'          => 'departamento 0',
                        'city'           => 'acapulco de juarez',
                        'state'          => 'Guerrero',
                        'postal_code'    => '39897',
                        'country_code'   => 'MX',
                    ],
                    'phone'        => '7440000000',
                    'country_code' => 'MX',
                ],
            ],
            'transactions' => [
                [
                    'amount' => [
                        'total'    => '14920.00',
                        'currency' => 'MXN',
                        'details'  => [
                            'subtotal'          => '14920.00',
                            'shipping'          => '0.00',
                            'insurance'         => '0.00',
                            'handling_fee'      => '0.00',
                            'shipping_discount' => '0.00',
                        ],
                    ],
                    'payee' => [
                        'merchant_id' => 'W7TLF00000000',
                        'email'       => 'payee@example.com',
                    ],
                    'description'     => 'My Shop',
                    'invoice_number'  => 'ord_ckcuv3md0000012s1npx00000',
                    'soft_descriptor' => 'PAYPAL *COLECOLLECT',
                    'item_list'       => [
                        'items' => [
                            [
                                'name'        => 'x1 MY PRODUCT NAME / 30',
                                'sku'         => '123456_ABC',
                                'description' => 'MY PRODUCT NAME / 30',
                                'price'       => '4000.00',
                                'currency'    => 'MXN',
                                'tax'         => '0.00',
                                'quantity'    => 1,
                            ],
                        ],
                        'shipping_address' => [
                            'recipient_name' => 'juan tremendo',
                            'line1'          => 'av costera de las palmas 2000',
                            'line2'          => 'departamento 0',
                            'city'           => 'acapulco de juarez',
                            'state'          => 'Guerrero',
                            'postal_code'    => '39897',
                            'country_code'   => 'MX',
                        ],
                    ],
                    'related_resources' => [
                        [
                            'sale' => [
                                'id'     => '03P43844491000000',
                                'state'  => 'refunded',
                                'amount' => [
                                    'total'    => '14920.00',
                                    'currency' => 'MXN',
                                    'details'  => [
                                        'subtotal'          => '14920.00',
                                        'shipping'          => '0.00',
                                        'insurance'         => '0.00',
                                        'handling_fee'      => '0.00',
                                        'shipping_discount' => '0.00',
                                    ],
                                ],
                                'payment_mode'           => 'INSTANT_TRANSFER',
                                'protection_eligibility' => 'INELIGIBLE',
                                'transaction_fee'        => [
                                    'value'    => '2851.38',
                                    'currency' => 'MXN',
                                ],
                                'receipt_id'     => '5280176468300000',
                                'parent_payment' => 'PAYID-L40000000000000000000000',
                                'create_time'    => '2020-07-20T18:53:01Z',
                                'update_time'    => '2020-07-31T01:07:40Z',
                                'links'          => [
                                    [
                                        'href'   => 'https://api.paypal.com/v1/payments/sale/03P43844491000000',
                                        'rel'    => 'self',
                                        'method' => 'GET',
                                    ],
                                    [
                                        'href'   => 'https://api.paypal.com/v1/payments/sale/03P43844491000000/refund',
                                        'rel'    => 'refund',
                                        'method' => 'POST',
                                    ],
                                    [
                                        'href'   => 'https://api.paypal.com/v1/payments/payment/PAYID-L40000000000000000000000',
                                        'rel'    => 'parent_payment',
                                        'method' => 'GET',
                                    ],
                                ],
                                'soft_descriptor' => 'PAYPAL *COLECOLLECT',
                            ],
                        ],
                        [
                            'refund' => [
                                'id'     => '634467273V4000000',
                                'state'  => 'completed',
                                'amount' => [
                                    'total'    => '-14920.00',
                                    'currency' => 'MXN',
                                ],
                                'parent_payment' => 'PAYID-L40000000000000000000000',
                                'sale_id'        => '03P43844491000000',
                                'create_time'    => '2020-07-21T10:31:01Z',
                                'update_time'    => '2020-07-21T10:31:01Z',
                                'links'          => [
                                    [
                                        'href'   => 'https://api.paypal.com/v1/payments/refund/634467273V4000000',
                                        'rel'    => 'self',
                                        'method' => 'GET',
                                    ],
                                    [
                                        'href'   => 'https://api.paypal.com/v1/payments/payment/PAYID-L40000000000000000000000',
                                        'rel'    => 'parent_payment',
                                        'method' => 'GET',
                                    ],
                                    [
                                        'href'   => 'https://api.paypal.com/v1/payments/sale/03P43844491000000',
                                        'rel'    => 'sale',
                                        'method' => 'GET',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'create_time' => '2020-07-20T18:52:29Z',
            'update_time' => '2020-07-31T01:07:40Z',
            'links'       => [
                [
                    'href'   => 'https://api.paypal.com/v1/payments/payment/PAYID-L40000000000000000000000',
                    'rel'    => 'self',
                    'method' => 'GET',
                ],
            ],
        ];
    }
}
