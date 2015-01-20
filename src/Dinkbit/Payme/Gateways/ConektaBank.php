<?php

namespace Dinkbit\Payme\Gateways;

use Dinkbit\PayMe\Status;
use Dinkbit\PayMe\Transaction;

class ConektaBank extends Conekta
{
    protected $displayName = 'conektabank';

    /**
     * {@inheritdoc}
     */
    public function charge($amount, $payment, $options = [])
    {
        $params = [];

        $params['bank']['type'] = $payment;

        $params = $this->addOrder($params, $amount, $options);

        return $this->commit('post', $this->buildUrlFromString('charges'), $params);
    }

    /**
     * {@inheritdoc}
     */
    public function mapResponseToTransaction($success, $response)
    {
        return (new Transaction())->setRaw($response)->map([
            'isRedirect'      => false,
            'success'         => $success,
            'message'         => $success ? $response['payment_method']['reference'] : $response['message_to_purchaser'],
            'test'            => array_key_exists('livemode', $response) ? $response["livemode"] : false,
            'authorization'   => $success ? $response['id'] : $response['type'],
            'status'          => $success ? $this->getStatus($this->array_get($response, 'status', 'paid')) : new Status('failed'),
            'reference'       => $success ? $response['payment_method']['reference'] : false,
            'code'            => $success ? $response['payment_method']['service_number'] : $response['code'],
        ]);
    }
}
