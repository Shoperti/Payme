<?php

namespace Dinkbit\Payme\Gateways;

use Dinkbit\PayMe\Gateways\Paypal\PaypalCommon;

class PaypalExpress extends PaypalCommon
{
    protected $liveEndpoint = 'https://www.paypal.com/webscr';
    protected $testEndpoint = 'https://www.sandbox.paypal.com/webscr';
    protected $displayName = 'paypalexpress';

    /**
     * @param $config
     */
    public function __construct($config)
    {
        $this->requires($config, ['login', 'password', 'signature']);
    }

    /**
     * Map the raw transaction array to a PayMe Transaction instance.
     *
     * @param array $transaction
     *
     * @return \Dinkbit\PayMe\Transaction
     */
    protected function mapResponseToTransaction(array $transaction)
    {
        return (new Transaction())->setRaw($transaction)->map([
            'isSuccessful' => true,
            'isRedirect'   => true,
            'code'         => isset($transaction['code_auth']) ? $transaction['code_auth'] : null,
            'message'      => null,
        ]);
    }

    public function getRedirectUrl()
    {
        $query = [
            'cmd'        => '_express-checkout',
            'useraction' => 'commit',
            'token'      => '123',
        ];

        return $this->testCheckoutEndpoint.'?'.http_build_query($query, '', '&');
    }

    /**
     * @return mixed
     */
    protected function getRequestUrl()
    {
        return $this->liveEndpoint;
    }
}
