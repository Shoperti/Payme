<?php

namespace Shoperti\PayMe\Gateways\Stripe;

use BadMethodCallException;
use Shoperti\PayMe\Contracts\CustomerInterface;
use Shoperti\PayMe\Gateways\AbstractApi;
use Shoperti\PayMe\Support\Arr;

/**
 * This is the stripe customers class.
 *
 * @author Joseph Cohen <joseph.cohen@dinkbit.com>
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
        throw new BadMethodCallException();
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
        $params['email'] = Arr::get($attributes, 'email');
        $params['description'] = Arr::get($attributes, 'name');

        if (isset($attributes['card'])) {
            $params['card'] = Arr::get($attributes, 'card', []);
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
        throw new BadMethodCallException();
    }
}
