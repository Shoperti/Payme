<?php

namespace Shoperti\PayMe\Gateways\Conekta;

use Shoperti\PayMe\Contracts\RecipientInterface;
use Shoperti\PayMe\Gateways\AbstractApi;

/**
 * This is the conekta recipients class.
 *
 * @author joseph.cohen@dinkbit.com
 */
class Recipients extends AbstractApi implements RecipientInterface
{
    /**
     * Charge the payout.
     *
     * @param int      $amount
     * @param mixed    $payment
     * @param string[] $options
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
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
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function create($options = [])
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
     * @param string   $id
     * @param string[] $options
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function delete($id, $options = [])
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
}
