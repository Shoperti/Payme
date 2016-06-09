<?php

namespace Shoperti\PayMe\Gateways\Conekta;

use Shoperti\PayMe\Contracts\RecipientInterface;
use Shoperti\PayMe\Gateways\AbstractApi;
use Shoperti\PayMe\Support\Arr;

/**
 * This is the Conekta recipients class.
 *
 * @author Joseph Cohen <joseph.cohen@dinkbit.com>
 */
class Recipients extends AbstractApi implements RecipientInterface
{
    /**
     * Register a recipient.
     *
     * @param string[] $attributes
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function create($attributes = [])
    {
        $params = [];

        $params = $this->addPayout($params, $attributes);
        $params = $this->addPayoutMethod($params, $attributes);
        $params = $this->addPayoutBilling($params, $attributes);

        return $this->gateway->commit('post', $this->gateway->buildUrlFromString('payees'), $params);
    }

    // /**
    //  * Update a recipient.
    //  *
    //  * @param int|string $reference
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
    //     return $this->commit('put', $this->gateway->buildUrlFromString('payees/'.$reference), $params);
    // }

    /**
     * Unstore an existing recipient.
     *
     * @param string   $id
     * @param string[] $options
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function delete($id, $options = [])
    {
        return $this->gateway->commit('delete', $this->gateway->buildUrlFromString('payees/'.$id));
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
