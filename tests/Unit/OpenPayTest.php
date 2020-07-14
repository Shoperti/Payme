<?php

namespace Shoperti\Tests\PayMe\Unit;

use Shoperti\PayMe\Gateways\OpenPay\OpenPayGateway;

class OpenPayGatewayTest extends AbstractTestCase
{
    protected $gatewayData = [
        'class'  => OpenPayGateway::class,
        'config' => 'open_pay',
    ];

    /** @test */
    public function it_should_parse_an_approved_payment()
    {
        $this->approvedPaymentTest($this->getApprovedPayment());
    }

    /** @test */
    public function it_should_parse_an_in_progress_payment()
    {
        $this->pendingPaymentTest($this->getInProgressPayment());
    }

    /** @test */
    public function it_should_parse_a_failed_payment()
    {
        $this->failedTPaymentTest($this->getFailedPayment(), 'Error description here');
    }

    private function getApprovedPayment()
    {
        return [
            'id'               => 'tr6cxbcefzatd10guvvw',
            'amount'           => 100.00,
            'authorization'    => '801585',
            'method'           => 'card',
            'operation_type'   => 'in',
            'transaction_type' => 'charge',
            'card'             => [
                'type'             => 'debit',
                'brand'            => 'visa',
                'address'          => null,
                'card_number'      => '411111XXXXXX1111',
                'holder_name'      => 'Juan Perez Ramirez',
                'expiration_year'  => '20',
                'expiration_month' => '12',
                'allows_charges'   => true,
                'allows_payouts'   => true,
                'bank_name'        => 'Banamex',
                'bank_code'        => '002',
            ],
            'status' => 'completed',
            'refund' => [
                'id'               => 'trcbsmjkroqmjobxqhpb',
                'amount'           => 100.00,
                'authorization'    => '801585',
                'method'           => 'card',
                'operation_type'   => 'out',
                'transaction_type' => 'refund',
                'status'           => 'completed',
                'currency'         => 'MXN',
                'creation_date'    => '2014-05-26T13:56:21-05:00',
                'operation_date'   => '2014-05-26T13:56:21-05:00',
                'description'      => 'devolucion',
                'error_message'    => null,
                'order_id'         => null,
                'customer_id'      => 'ag4nktpdzebjiye1tlze',
            ],
            'currency'       => 'MXN',
            'creation_date'  => '2014-05-26T11:56:25-05:00',
            'operation_date' => '2014-05-26T11:56:25-05:00',
            'description'    => 'Cargo inicial a mi cuenta',
            'error_message'  => null,
            'order_id'       => 'oid-00052',
            'customer_id'    => 'ag4nktpdzebjiye1tlze',
        ];
    }

    private function getInProgressPayment()
    {
        $payload = $this->getApprovedPayment();
        $payload['status'] = 'in_progress';

        return $payload;
    }

    private function getFailedPayment()
    {
        return [
            'category'    => 'request',
            'description' => 'Error description here',
            'http_code'   => 404,
            'error_code'  => 1005,
            'request_id'  => '1981cdb8-19cb-4bad-8256-e95d58bc035c',
            'fraud_rules' => [
                'Billing <> BIN Country for VISA/MC',
            ],
        ];
    }
}
