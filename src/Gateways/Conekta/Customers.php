<?php

namespace Shoperti\PayMe\Gateways\Conekta;

use Shoperti\PayMe\Contracts\CustomerInterface;
use Shoperti\PayMe\Gateways\AbstractApi;
use Shoperti\PayMe\Support\Arr;

/**
 * This is the Conekta customers class.
 *
 * @author Arturo Rodríguez <arturo.rodriguez@dinkbit.com>
 */
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
        return $this->gateway->commit('get', $this->gateway->buildUrlFromString('customers').'/'.$customer);
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

        if (isset($attributes['phone'])) {
            $params['phone'] = $attributes['phone'];
        }

        if (isset($attributes['card'])) {
            $params['payment_sources'] = [[
                'token_id' => $attributes['card'],
                'type'     => 'card'
            ]];
        }

        return $this->gateway->commit('post', $this->gateway->buildUrlFromString('customers'), $params);
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
        $params = [];

        if (isset($attributes['name'])) {
            $params['name'] = $attributes['name'];
        }

        if (isset($attributes['email'])) {
            $params['email'] = $attributes['email'];
        }

        if (isset($attributes['phone'])) {
            $params['phone'] = $attributes['phone'];
        }

        if (isset($attributes['default_card'])) {
            $params['default_payment_source_id'] = $attributes['default_card'];
        }

        return $this->gateway->commit('put', $this->gateway->buildUrlFromString("customers/{$customer}"), $params);
    }
}
