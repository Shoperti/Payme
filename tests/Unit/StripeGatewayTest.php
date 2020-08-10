<?php

namespace Shoperti\Tests\PayMe\Unit;

use Shoperti\PayMe\Gateways\Stripe\StripeGateway;

class StripeGatewayTest extends AbstractTestCase
{
    protected $gatewayData = [
        'class'                  => StripeGateway::class,
        'config'                 => 'stripe',
        'innerMethodExtraParams' => ['[]'],
    ];

    /**
     * @test
     * charges()->create()
     */
    public function it_should_parse_a_created_payment()
    {
        $this->createSuccessfulChargeWithRedirectTest($this->getSuccessfulPaymentCreationIntent());
    }

    /**
     * @test
     * charges()->get()
     */
    public function it_should_parse_a_got_payment()
    {
        $this->approvedPaymentTest($this->getCompletedPayment());
    }

    /**
     * @test
     * charges()->create()
     */
    public function it_should_parse_a_rejected_payment()
    {
        $payload = $this->getDeclinedPayment();
        $this->declinedPaymentTest($payload, $payload['error']['message']);
    }

    private function getSuccessfulPaymentCreationIntent()
    {
        return [
            'id'                     => 'pi_1H5ELO2eZvKYlo2CeDTDDfY2',
            'object'                 => 'payment_intent',
            'amount'                 => 1099,
            'amount_capturable'      => 0,
            'amount_received'        => 0,
            'application'            => null,
            'application_fee_amount' => null,
            'canceled_at'            => null,
            'cancellation_reason'    => null,
            'capture_method'         => 'automatic',
            'charges'                => [
                'object'      => 'list',
                'data'        => [],
                'has_more'    => false,
                'total_count' => 0,
                'url'         => '/v1/charges?payment_intent=pi_1H5ELO2eZvKYlo2CeDTDDfY2',
            ],
            'client_secret'       => 'pi_1H5ELO2eZvKYlo2CeDTDDfY2_secret_qRdl4VI5zrCfPYu2Pk0SK6kSO',
            'confirmation_method' => 'automatic',
            'created'             => 1594833874,
            'currency'            => 'usd',
            'customer'            => null,
            'description'         => null,
            'invoice'             => null,
            'last_payment_error'  => null,
            'livemode'            => false,
            'metadata'            => [
                'integration_check' => 'accept_a_payment',
            ],
            'next_action'            => null,
            'on_behalf_of'           => null,
            'payment_method'         => null,
            'payment_method_options' => [
                'card' => [
                    'installments'           => null,
                    'network'                => null,
                    'request_three_d_secure' => 'automatic',
                ],
            ],
            'payment_method_types' => [
                0 => 'card',
            ],
            'receipt_email'               => null,
            'review'                      => null,
            'setup_future_usage'          => null,
            'shipping'                    => null,
            'source'                      => null,
            'statement_descriptor'        => null,
            'statement_descriptor_suffix' => null,
            'status'                      => 'requires_payment_method',
            'transfer_data'               => null,
            'transfer_group'              => null,
        ];
    }

    private function getCompletedPayment()
    {
        return [
            'id'                     => 'pi_1H772HCiECZYUTf4Ksgl8s2v',
            'object'                 => 'payment_intent',
            'amount'                 => 23200,
            'amount_capturable'      => 0,
            'amount_received'        => 23200,
            'application'            => null,
            'application_fee_amount' => null,
            'canceled_at'            => null,
            'cancellation_reason'    => null,
            'capture_method'         => 'automatic',
            'charges'                => [
                'object' => 'list',
                'data'   => [
                    [
                        'id'                     => 'ch_1H77j9CiECZYUTf4p91dTNtR',
                        'object'                 => 'charge',
                        'amount'                 => 23200,
                        'amount_refunded'        => 0,
                        'application'            => null,
                        'application_fee'        => null,
                        'application_fee_amount' => null,
                        'balance_transaction'    => 'txn_1H77j9CiECZYUTf4sM2aNg4W',
                        'billing_details'        => [
                            'address' => [
                                'city'        => null,
                                'country'     => null,
                                'line1'       => null,
                                'line2'       => null,
                                'postal_code' => '12345',
                                'state'       => null,
                            ],
                            'email' => null,
                            'name'  => 'Nombre completo',
                            'phone' => null,
                        ],
                        'calculated_statement_descriptor' => 'Stripe',
                        'captured'                        => true,
                        'created'                         => 1595285095,
                        'currency'                        => 'mxn',
                        'customer'                        => null,
                        'description'                     => null,
                        'destination'                     => null,
                        'dispute'                         => null,
                        'disputed'                        => false,
                        'failure_code'                    => null,
                        'failure_message'                 => null,
                        'fraud_details'                   => [],
                        'invoice'                         => null,
                        'livemode'                        => false,
                        'metadata'                        => [
                            'integration_check' => 'accept_a_payment',
                        ],
                        'on_behalf_of' => null,
                        'order'        => null,
                        'outcome'      => [
                            'network_status' => 'approved_by_network',
                            'reason'         => null,
                            'risk_level'     => 'normal',
                            'risk_score'     => 45,
                            'seller_message' => 'Payment complete.',
                            'type'           => 'authorized',
                        ],
                        'paid'                   => true,
                        'payment_intent'         => 'pi_1H772HCiECZYUTf4Ksgl8s2v',
                        'payment_method'         => 'pm_1H77j9CiECZYUTf4KhEik68x',
                        'payment_method_details' => [
                            'card' => [
                                'brand'  => 'visa',
                                'checks' => [
                                    'address_line1_check'       => null,
                                    'address_postal_code_check' => 'pass',
                                    'cvc_check'                 => 'pass',
                                ],
                                'country'        => 'US',
                                'exp_month'      => 12,
                                'exp_year'       => 2034,
                                'fingerprint'    => 'x2WmCYuZZwfD6Eut',
                                'funding'        => 'credit',
                                'installments'   => null,
                                'last4'          => '4242',
                                'network'        => 'visa',
                                'three_d_secure' => null,
                                'wallet'         => null,
                            ],
                            'type' => 'card',
                        ],
                        'receipt_email'  => null,
                        'receipt_number' => null,
                        'receipt_url'    => 'https://pay.stripe.com/receipts/acct_1H5IinCiECZYUTf4/ch_1H77j9CiECZYUTf4p91dTNtR/rcpt_HgUifqz4bihngbfafwcp0fdYbxOh5Fn',
                        'refunded'       => false,
                        'refunds'        => [
                            'object'      => 'list',
                            'data'        => [],
                            'has_more'    => false,
                            'total_count' => 0,
                            'url'         => '/v1/charges/ch_1H77j9CiECZYUTf4p91dTNtR/refunds',
                        ],
                        'review'                      => null,
                        'shipping'                    => null,
                        'source'                      => null,
                        'source_transfer'             => null,
                        'statement_descriptor'        => null,
                        'statement_descriptor_suffix' => null,
                        'status'                      => 'succeeded',
                        'transfer_data'               => null,
                        'transfer_group'              => null,
                    ],
                ],
                'has_more'    => false,
                'total_count' => 1,
                'url'         => '/v1/charges?payment_intent=pi_1H772HCiECZYUTf4Ksgl8s2v',
            ],
            'client_secret'       => 'pi_1H772HCiECZYUTf4Ksgl8s2v_secret_aTk5oMzrSZaSPK9CLM04JOMxH',
            'confirmation_method' => 'automatic',
            'created'             => 1595282437,
            'currency'            => 'mxn',
            'customer'            => null,
            'description'         => null,
            'invoice'             => null,
            'last_payment_error'  => null,
            'livemode'            => false,
            'metadata'            => [
                'integration_check' => 'accept_a_payment',
            ],
            'next_action'            => null,
            'on_behalf_of'           => null,
            'payment_method'         => 'pm_1H77j9CiECZYUTf4KhEik68x',
            'payment_method_options' => [
                'card' => [
                    'installments'           => null,
                    'network'                => null,
                    'request_three_d_secure' => 'automatic',
                ],
            ],
            'payment_method_types' => [
                0 => 'card',
            ],
            'receipt_email'               => null,
            'review'                      => null,
            'setup_future_usage'          => null,
            'shipping'                    => null,
            'source'                      => null,
            'statement_descriptor'        => null,
            'statement_descriptor_suffix' => null,
            'status'                      => 'succeeded',
            'transfer_data'               => null,
            'transfer_group'              => null,
        ];
    }

    private function getDeclinedPayment()
    {
        return [
            'error' => [
                'message' => 'Invalid currency: usdz. Stripe currently supports these currencies: usd, aed, afn, all, amd, ang, aoa, ars, aud, awg, azn, bam, bbd, bdt, bgn, bif, bmd, bnd, bob, brl, bsd, bwp, bzd, cad, cdf, chf, clp, cny, cop, crc, cve, czk, djf, dkk, dop, dzd, egp, etb, eur, fjd, fkp, gbp, gel, gip, gmd, gnf, gtq, gyd, hkd, hnl, hrk, htg, huf, idr, ils, inr, isk, jmd, jpy, kes, kgs, khr, kmf, krw, kyd, kzt, lak, lbp, lkr, lrd, lsl, mad, mdl, mga, mkd, mmk, mnt, mop, mro, mur, mvr, mwk, mxn, myr, mzn, nad, ngn, nio, nok, npr, nzd, pab, pen, pgk, php, pkr, pln, pyg, qar, ron, rsd, rub, rwf, sar, sbd, scr, sek, sgd, shp, sll, sos, srd, std, szl, thb, tjs, top, try, ttd, twd, tzs, uah, ugx, uyu, uzs, vnd, vuv, wst, xaf, xcd, xof, xpf, yer, zar, zmw, eek, lvl, svc, vef, ltl',
                'param'   => 'currency',
                'type'    => 'invalid_request_error',
            ],
        ];
    }
}
