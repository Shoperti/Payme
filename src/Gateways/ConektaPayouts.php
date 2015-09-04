<?php

namespace Shoperti\PayMe\Gateways;

use Shoperti\PayMe\Status;
use Shoperti\PayMe\Support\Arr;
use Shoperti\PayMe\Transaction;

class ConektaPayouts extends Conekta
{
    /**
     * Gateway display name.
     *
     * @var string
     */
    protected $displayName = 'conekta_payouts';

    /**
     * Charge the payout.
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

        $params = $this->addOrder($params, $amount, $options);
        $params = $this->addPaymentMethod($params, $payment, $options);

        return $this->commit('post', $this->buildUrlFromString('payouts'), $params);
    }

    /**
     * Register a recipient.
     *
     * @param string[] $options
     *
     * @return \Shoperti\PayMe\Transaction
     */
    public function storeRecipient($options = [])
    {
        $params = [];

        $params = $this->addPayout($params, $options);
        $params = $this->addPayoutMethod($params, $options);
        $params = $this->addPayoutBilling($params, $options);

        return $this->commit('post', $this->buildUrlFromString('payees'), $params);
    }

    // /**
    //  * @param $reference
    //  * @param array $options
    //  *
    //  * @return mixed
    //  */
    // public function update($reference, $options = [])
    // {
    //     // TODO: Check as there's an error from Conekta
    //     $params = [];
    //
    //     $params = $this->addPayout($params, $options);
    //     $params = $this->addPayoutMethod($params, $options);
    //     $params = $this->addPayoutBilling($params, $options);
    //
    //     return $this->commit('put', $this->buildUrlFromString('payees/'.$reference), $params);
    // }

    /**
     * Unstores an existing recipient.
     *
     * @param string   $reference
     * @param string[] $options
     *
     * @return \Shoperti\PayMe\Transaction
     */
    public function unstoreRecipient($reference, $options = [])
    {
        return $this->commit('delete', $this->buildUrlFromString('payees/'.$reference));
    }

    /**
     * Add payment method to request.
     *
     * @param string[] $params
     * @param mixed    $payment
     * @param string[] $options
     *
     * @return array
     */
    protected function addPaymentMethod(array $params, $payment, array $options)
    {
        if (is_string($payment)) {
            $params['payee_id'] = $payment;
        }

        return $params;
    }

    /**
     * Add payout to request.
     *
     * @param string[] $params
     * @param string[] $options
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
     * Add payout method to request.
     *
     * @param string[] $params
     * @param string[] $options
     *
     * @return mixed
     */
    protected function addPayoutMethod(array $params, array $options)
    {
        $params['payout_method'] = [];
        $params['payout_method']['type'] = 'bank_transfer_payout_method';
        $params['payout_method']['account_number'] = Arr::get($options, 'account_number');
        $params['payout_method']['account_holder'] = Arr::get($options, 'account_holder');

        return $params;
    }

    /**
     * Add payout billing to request.
     *
     * @param string[] $params
     * @param string[] $options
     *
     * @return mixed
     */
    protected function addPayoutBilling(array $params, array $options)
    {
        $params['billing_address'] = [];
        $params['billing_address']['tax_id'] = Arr::get($options, 'tax_id'); // RFC

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
