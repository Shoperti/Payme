<?php

namespace Shoperti\Tests\PayMe\Functional;

use Shoperti\PayMe\PayMe;

class GatewaysTest extends AbstractFunctionalTestCase
{
    private $configForGateway = [
        'bogus'             => \Shoperti\PayMe\Gateways\Bogus\BogusGateway::class,
        'conekta'           => \Shoperti\PayMe\Gateways\Conekta\ConektaGateway::class,
        'manual'            => \Shoperti\PayMe\Gateways\Manual\ManualGateway::class,
        'mercadopago'       => \Shoperti\PayMe\Gateways\MercadoPago\MercadoPagoGateway::class,
        'mercadopago_basic' => \Shoperti\PayMe\Gateways\MercadoPagoBasic\MercadoPagoBasicGateway::class,
        'open_pay'          => \Shoperti\PayMe\Gateways\OpenPay\OpenPayGateway::class,
        'paypal'            => \Shoperti\PayMe\Gateways\PaypalExpress\PaypalExpressGateway::class,
        'paypal_plus'       => \Shoperti\PayMe\Gateways\PaypalPlus\PaypalPlusGateway::class,
        'sr_pago'           => \Shoperti\PayMe\Gateways\SrPago\SrPagoGateway::class,
        'stripe'            => \Shoperti\PayMe\Gateways\Stripe\StripeGateway::class,
    ];

    /** @test */
    public function it_should_create_gateways()
    {
        foreach ($this->configForGateway as $config => $gatewayClass) {
            $sections = explode('\\', $gatewayClass);
            $sections[count($sections) - 1] = 'Charges';
            $chargesClass = implode('\\', $sections);

            $payMe = PayMe::make($this->getCredentials($config));

            $this->assertInstanceOf($gatewayClass, $payMe->getGateway());
            $this->assertInstanceOf($chargesClass, $payMe->charges());
        }
    }
}
