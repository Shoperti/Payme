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
            $params['cards'] = [$attributes['card']];
        }

        return $this->gateway->commit('post', $this->gateway->buildUrlFromString('customers'), $params);
    }
 }
