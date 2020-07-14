<?php

namespace Shoperti\Tests\PayMe\Unit;

use Shoperti\PayMe\Gateways\PaypalExpress\PaypalExpressGateway;

/**
 * Tests responses from https://api.mercadopago.com/merchant_orders/$id?access_token=$token.
 */
class PaypalExpressGatewayTest extends AbstractTestCase
{
    protected $gatewayData = [
        'class'                  => PaypalExpressGateway::class,
        'config'                 => 'paypal',
        'innerMethod'            => 'respond',
        'innerMethodExtraParams' => ['[]', '[]'],
    ];

    public function setUp()
    {
        parent::setUp();

        // on this gateway, a flag is set on the payload before calling the parse method
        $this->gateway->preprocessPayload = function ($response) {
            $response['isRedirect'] = false;

            return $response;
        };
    }

    /** @test */
    public function it_should_parse_an_approved_payment()
    {
        $this->approvedPaymentTest($this->getApprovedPayment(), 'Success');
    }

    /** @test */
    public function it_should_parse_a_pending_payment()
    {
        $this->pendingPaymentTest($this->getPendingPayment(), 'Success');
    }

    /** @test */
    public function it_should_parse_a_failed_payment()
    {
        $this->failedTPaymentTest($this->getFailedPayment(), "This transaction couldn't be completed. Please redirect your customer to PayPal.");
    }

    private function getApprovedPayment()
    {
        return [
            'TOKEN'                                   => 'EC-11111111111111111',
            'SUCCESSPAGEREDIRECTREQUESTED'            => 'false',
            'TIMESTAMP'                               => '2019-11-24T00:03:27Z',
            'CORRELATIONID'                           => '1111111111111',
            'ACK'                                     => 'Success',
            'VERSION'                                 => '119.0',
            'BUILD'                                   => '53842365',
            'INSURANCEOPTIONSELECTED'                 => 'false',
            'SHIPPINGOPTIONISDEFAULT'                 => 'false',
            'PAYMENTINFO_0_TRANSACTIONID'             => '11111111111111111',
            'PAYMENTINFO_0_TRANSACTIONTYPE'           => 'cart',
            'PAYMENTINFO_0_PAYMENTTYPE'               => 'instant',
            'PAYMENTINFO_0_ORDERTIME'                 => '2019-11-24T00:03:24Z',
            'PAYMENTINFO_0_AMT'                       => '491.30',
            'PAYMENTINFO_0_FEEAMT'                    => '27.14',
            'PAYMENTINFO_0_TAXAMT'                    => '0.00',
            'PAYMENTINFO_0_CURRENCYCODE'              => 'MXN',
            'PAYMENTINFO_0_PAYMENTSTATUS'             => 'Completed',
            'PAYMENTINFO_0_PENDINGREASON'             => 'None',
            'PAYMENTINFO_0_REASONCODE'                => 'None',
            'PAYMENTINFO_0_PROTECTIONELIGIBILITY'     => 'Eligible',
            'PAYMENTINFO_0_PROTECTIONELIGIBILITYTYPE' => 'ItemNotReceivedEligible,UnauthorizedPaymentEligible',
            'PAYMENTINFO_0_SELLERPAYPALACCOUNTID'     => 'owner@example.com',
            'PAYMENTINFO_0_SECUREMERCHANTACCOUNTID'   => 'XXXXXXXXXXXXX',
            'PAYMENTINFO_0_ERRORCODE'                 => '0',
            'PAYMENTINFO_0_ACK'                       => 'Success',
        ];
    }

    private function getPendingPayment()
    {
        return [
            'TOKEN'                                   => 'EC-22222222222222222',
            'SUCCESSPAGEREDIRECTREQUESTED'            => 'false',
            'TIMESTAMP'                               => '2019-11-25T00:57:26Z',
            'CORRELATIONID'                           => '2222222222222',
            'ACK'                                     => 'Success',
            'VERSION'                                 => '119.0',
            'BUILD'                                   => '53842365',
            'INSURANCEOPTIONSELECTED'                 => 'false',
            'SHIPPINGOPTIONISDEFAULT'                 => 'false',
            'PAYMENTINFO_0_TRANSACTIONID'             => '22222222222222222',
            'PAYMENTINFO_0_TRANSACTIONTYPE'           => 'cart',
            'PAYMENTINFO_0_PAYMENTTYPE'               => 'instant',
            'PAYMENTINFO_0_ORDERTIME'                 => '2019-11-25T00:57:23Z',
            'PAYMENTINFO_0_AMT'                       => '1078.00',
            'PAYMENTINFO_0_TAXAMT'                    => '0.00',
            'PAYMENTINFO_0_CURRENCYCODE'              => 'MXN',
            'PAYMENTINFO_0_PAYMENTSTATUS'             => 'Pending',
            'PAYMENTINFO_0_PENDINGREASON'             => 'regulatoryreview',
            'PAYMENTINFO_0_REASONCODE'                => 'None',
            'PAYMENTINFO_0_PROTECTIONELIGIBILITY'     => 'Eligible',
            'PAYMENTINFO_0_PROTECTIONELIGIBILITYTYPE' => 'ItemNotReceivedEligible,UnauthorizedPaymentEligible',
            'PAYMENTINFO_0_SELLERPAYPALACCOUNTID'     => 'owner@example.com',
            'PAYMENTINFO_0_SECUREMERCHANTACCOUNTID'   => 'XXXXXXXXXXXXX',
            'PAYMENTINFO_0_ERRORCODE'                 => '0',
            'PAYMENTINFO_0_ACK'                       => 'Success',
        ];
    }

    private function getFailedPayment()
    {
        return [
            'TOKEN'                        => 'EC-33333333333333333',
            'SUCCESSPAGEREDIRECTREQUESTED' => 'false',
            'TIMESTAMP'                    => '2019-05-09T19:48:04Z',
            'CORRELATIONID'                => '3333333333333',
            'ACK'                          => 'Failure',
            'VERSION'                      => '119.0',
            'BUILD'                        => '52688019',
            'L_ERRORCODE0'                 => '10486',
            'L_SHORTMESSAGE0'              => 'This transaction couldn\'t be completed.',
            'L_LONGMESSAGE0'               => 'This transaction couldn\'t be completed. Please redirect your customer to PayPal.',
            'L_SEVERITYCODE0'              => 'Error',
        ];
    }
}
