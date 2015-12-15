<?php

namespace Shoperti\PayMe\Gateways\Stripe;

use Shoperti\PayMe\Contracts\CustomerInterface;
use Shoperti\PayMe\Gateways\AbstractApi;
use Shoperti\PayMe\Support\Arr;

/**
  * This is the stripe customers class.
  *
  * @author joseph.cohen@dinkbit.com
  */
 class Customers extends AbstractApi implements CustomerInterface
 {
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
 }
