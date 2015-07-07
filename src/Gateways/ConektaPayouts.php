<?php

namespace Dinkbit\PayMe\Gateways;

use Dinkbit\PayMe\Status;
use Dinkbit\PayMe\Support\Arr;
use Dinkbit\PayMe\Transaction;

class ConektaPayouts extends Conekta
{
    /**
     * Gateway display name.
     *
     * @var string
     */
    protected $displayName = 'conektapayouts';

    /**
     * Process Payout.
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

        $params = $this->addOrder($params, $amount, $options);
        $params = $this->addPaymentMethod($params, $payment, $options);

        return $this->commit('post', $this->buildUrlFromString('payouts'), $params);
    }

    /**
     * Create Recipient.
     *
     * @param array $options
     *
     * @return mixed
     */
    public function storeRecipient($options = [])
    {
        $params = [];

        $params = $this->addPayout($params, $options);
        $params = $this->addPayoutMethod($params, $options);
        $params = $this->addPayoutBilling($params, $options);

        return $this->commit('post', $this->buildUrlFromString('payees'), $params);
    }

    /**
     * @param $reference
     * @param array $options
     *
     * @return mixed
     *               Revisar ya que existe un error por parte de Conekta
     */

    /*
    public function update($reference, $options = [])
    {
        $params = [];

        $params = $this->addPayout($params, $options);
        $params = $this->addPayoutMethod($params, $options);
        $params = $this->addPayoutBilling($params, $options);

        return $this->commit('put', $this->buildUrlFromString('payees/'.$reference), $params);
    }
    */

    /**
     * {@inheritdoc}
     */
    public function unstoreRecipient($reference, $options = [])
    {
        return $this->commit('delete', $this->buildUrlFromString('payees/'.$reference));
    }

    /**
     * @param $params
     * @param $payment
     * @param $options
     *
     * @return mixed
     */
    protected function addPaymentMethod(array $params, $payment, array $options)
    {
        if (is_string($payment)) {
            $params['payee_id'] = $payment;
        }

        return $params;
    }

    /**
     * @param $params
     * @param $options
     *
     * @return mixed
     */
    protected function addPayout($params, $options)
    {
        $params['name'] = Arr::get($options, 'name');
        $params['email'] = Arr::get($options, 'email');
        $params['phone'] = Arr::get($options, 'phone');

        return $params;
    }

    /**
     * @param $params
     * @param $options
     *
     * @return mixed
     */
    protected function addPayoutMethod($params, $options)
    {
        $params['payout_method'] = [];
        $params['payout_method']['type'] = 'bank_transfer_payout_method';
        $params['payout_method']['account_number'] = Arr::get($options, 'account_number');
        $params['payout_method']['account_holder'] = Arr::get($options, 'account_holder');

        return $params;
    }

    /**
     * @param $params
     * @param $options
     *
     * @return mixed
     */
    protected function addPayoutBilling($params, $options)
    {
        $params['billing_address'] = [];
        $params['billing_address']['tax_id'] = Arr::get($options, 'tax_id'); // RFC

        return $params;
    }

    /**
     * {@inheritdoc}
     */
    public function mapResponseToTransaction($success, $response)
    {
        return (new Transaction())->setRaw($response)->map([
            'isRedirect'    => false,
            'success'       => $success,
            'message'       => $success ? 'Transacción aprobada' : $response['message_to_purchaser'],
            'test'          => array_key_exists('livemode', $response) ? $response['livemode'] : false,
            'authorization' => $success ? $response['id'] : $response['type'],
            'status'        => $success ? $this->getStatus(Arr::get($response, 'status', 'paid')) : new Status('failed'),
            'reference'     => $success ? $this->getReference($response) : false,
            'code'          => $success ? false : $response['code'],
        ]);
    }
}
