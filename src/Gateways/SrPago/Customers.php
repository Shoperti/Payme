<?php

namespace Shoperti\PayMe\Gateways\SrPago;

use Shoperti\PayMe\Contracts\CustomerInterface;
use Shoperti\PayMe\Gateways\AbstractApi;
use Shoperti\PayMe\Support\Arr;

class Customers extends AbstractApi implements CustomerInterface
{
    /**
     * Find a customer.
     *
     * @param string $customer
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function find($customer)
    {
        return $this->gateway->commit('get', $this->gateway->buildUrlFromString('customer/'.$customer));
    }

    /**
     * Create a customer.
     *
     * @param string[] $attributes
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function create($attributes = [])
    {
        $params = [
            'name'  => Arr::get($attributes, 'name'),
            'email' => Arr::get($attributes, 'email'),
        ];

        return $this->gateway->commit('post', $this->gateway->buildUrlFromString('customer'), $params);
    }

    /**
     * Update a customer.
     *
     * @param string   $customer
     * @param string[] $attributes
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function update($customer, $attributes = [])
    {
        $params = [
            'name'  => Arr::get($attributes, 'name'),
            'email' => Arr::get($attributes, 'email'),
        ];

        return $this->gateway->commit('put', $this->gateway->buildUrlFromString('customer/'.$customer), $params);
    }

    /**
     * Associate a card to a customer.
     *
     * @param string $customer
     * @param string $token
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function addCard($customer, $token)
    {
        $params = [
            'token' => $token,
        ];

        return $this->gateway->commit(
                'post',
                $this->gateway->buildUrlFromString('customer/'.$customer.'/cards'),
                $params
            );
    }

    /**
     * Delete a customer.
     *
     * @param string $customer
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function delete($customer)
    {
        return $this->gateway->commit('delete', $this->gateway->buildUrlFromString('customer/'.$customer));
    }
}
