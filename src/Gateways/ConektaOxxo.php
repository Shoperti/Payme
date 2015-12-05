<?php

namespace Shoperti\PayMe\Gateways;

use Shoperti\PayMe\Status;
use Shoperti\PayMe\Support\Arr;
use Shoperti\PayMe\Transaction;

class ConektaOxxo extends Conekta
{
    /**
     * Gateway display name.
     *
     * @var string
     */
    protected $displayName = 'conekta_oxxo';

    /**
     * Charge the credit card.
     *
     * @param int      $amount
     * @param mixed    $payment
     * @param string[] $options
     *
     * @return \Shoperti\PayMe\Transaction
     */
    public function charge($amount, $payment, $options = [])
    {
        $params = [];

        $params['cash']['type'] = $payment;

        $params = $this->addExpiry($params, $options);
        $params = $this->addOrder($params, $amount, $options);
        $params = $this->addOrderDetails($params, $options);

        return $this->commit('post', $this->buildUrlFromString('charges'), $params);
    }

    /**
     * Add payment expire at time.
     *
     * @param string[] $params
     * @param string[] $options
     *
     * @return array
     */
    protected function addExpiry(array $params, array $options)
    {
        $params['cash']['expires_at'] = Arr::get($options, 'expires', date('Y-m-d', time() + 172800));

        return $params;
    }

    /**
     * Commit a HTTP request.
     *
     * @param string   $method
     * @param string   $url
     * @param string[] $params
     * @param string[] $options
     *
     * @return \Shoperti\PayMe\Transaction
     */
    public function mapTransaction($success, $response)
    {
        return (new Transaction())->setRaw($response)->map([
            'isRedirect'      => false,
            'success'         => $success,
            'message'         => $success ? $response['payment_method']['barcode_url'] : $response['message_to_purchaser'],
            'test'            => array_key_exists('livemode', $response) ? $response['livemode'] : false,
            'authorization'   => $success ? $response['id'] : $response['type'],
            'status'          => $success ? $this->getStatus(Arr::get($response, 'status', 'paid')) : new Status('failed'),
            'reference'       => $success ? $response['payment_method']['barcode_url'] : false,
            'code'            => $success ? $response['payment_method']['barcode'] : $response['code'],
        ]);
    }
}
