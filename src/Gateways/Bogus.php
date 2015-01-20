<?php

namespace Dinkbit\PayMe\Gateways;

use Dinkbit\PayMe\Contracts\Charge;
use Dinkbit\PayMe\Contracts\Store;
use Dinkbit\PayMe\Status;
use Dinkbit\PayMe\Transaction;

class Bogus extends AbstractGateway implements Charge, Store
{
    /**
     * Gateway API endpoint.
     *
     * @var string
     */
    protected $endpoint = 'https://example.com';

    /**
     * Gateway display name.
     *
     * @var string
     */
    protected $displayName = 'bogus';

    /**
     * Gateway default currency.
     *
     * @var string
     */
    protected $defaultCurrency = 'USD';

    /**
     * Gateway money format.
     *
     * @var string
     */
    protected $moneyFormat = 'cents';

    /**
     * Inject the configuration for a Gateway.
     *
     * @param $config
     */
    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * Charge the credit card.
     *
     * @param $amount
     * @param $payment
     * @param string[] $options
     *
     * @return \Dinkbit\Payme\Transaction
     */
    public function charge($amount, $payment, $options = [])
    {
        $params = [];

        $params['transaction'] = 'fail';

        if ($payment === 'success') {
            $params['transaction'] = 'success';
        }

        return $this->commit('post', '', $params);
    }

    /**
     * Stores a credit card.
     *
     * @param $creditcard
     * @param string[] $options
     *
     * @return mixed
     */
    public function store($creditcard, $options = [])
    {
        $params = [];

        $params['transaction'] = 'fail';

        if ($creditcard === 'success') {
            $params['transaction'] = 'success';
        }

        return $this->commit('post', 'store', $params);
    }

    /**
     * Unstores a credit card.
     *
     * @param $reference
     * @param string[] $options
     *
     * @return mixed
     */
    public function unstore($reference, $options = [])
    {
        $params['transaction'] = 'fail';

        if ($creditcard === 'success') {
            $params['transaction'] = 'success';
        }

        return $this->commit('post', 'unstore', $params);
    }

    /**
     * Commit a HTTP request.
     *
     * @param string   $method
     * @param string   $url
     * @param string[] $params
     * @param string[] $options
     *
     * @return mixed
     */
    protected function commit($method = 'post', $url, $params = [], $options = [])
    {
        $success = false;
        $response = [];

        if ($params['transaction'] == 'success') {
            $success = true;
        }

        return $this->mapTransaction($success, $response);
    }

    /**
     * Map HTTP response to transaction object.
     *
     * @param bool  $success
     * @param array $response
     *
     * @return \Dinkbit\PayMe\Transaction
     */
    public function mapTransaction($success, $response)
    {
        return (new Transaction())->setRaw($response)->map([
            'isRedirect'      => false,
            'success'         => $success,
            'message'         => $success ? 'Approved' : 'Error',
            'test'            => false,
            'authorization'   => $success ? '123' : '',
            'status'          => $success ? new Status('paid') : new Status('failed'),
            'reference'       => $success ? '123' : false,
            'code'            => $success ? false : '1',
        ]);
    }

    /**
     * Get the request url.
     *
     * @return string
     */
    protected function getRequestUrl()
    {
        return $this->endpoint;
    }
}
