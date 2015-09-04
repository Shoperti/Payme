<?php

namespace Shoperti\PayMe\Gateways;

use Shoperti\PayMe\Status;
use Shoperti\PayMe\Support\Arr;
use Shoperti\PayMe\Transaction;

class ConektaBank extends Conekta
{
    /**
     * Gateway display name.
     *
     * @var string
     */
    protected $displayName = 'conekta_bank';

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

        $params['bank']['type'] = $payment;

        $params = $this->addOrder($params, $amount, $options);

        return $this->commit('post', $this->buildUrlFromString('charges'), $params);
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
            'message'         => $success ? $response['payment_method']['reference'] : $response['message_to_purchaser'],
            'test'            => array_key_exists('livemode', $response) ? $response['livemode'] : false,
            'authorization'   => $success ? $response['id'] : $response['type'],
            'status'          => $success ? $this->getStatus(Arr::get($response, 'status', 'paid')) : new Status('failed'),
            'reference'       => $success ? $response['payment_method']['reference'] : false,
            'code'            => $success ? $response['payment_method']['service_number'] : $response['code'],
        ]);
    }
}
