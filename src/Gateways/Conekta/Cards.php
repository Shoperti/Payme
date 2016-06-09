<?php

namespace Shoperti\PayMe\Gateways\Conekta;

use Shoperti\PayMe\Contracts\CardInterface;
use Shoperti\PayMe\Gateways\AbstractApi;
use Shoperti\PayMe\Support\Arr;

/**
  * This is the Conekta cards class.
  *
  * @author Joseph Cohen <joseph.cohen@dinkbit.com>
  */
 class Cards extends AbstractApi implements CardInterface
 {
     /**
     * Store a credit card.
     *
     * @param string   $creditcard
     * @param string[] $options
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function create($creditcard, $options = [])
    {
        if (isset($options['customer'])) {
            $params['token'] = $creditcard;

            return $this->gateway->commit('post', $this->gateway->buildUrlFromString('customers/'.$options['customer'].'/cards'), $params);
        } else {
            $params['email'] = Arr::get($options, 'email');
            $params['name'] = Arr::get($options, 'name');
            $params['cards'] = [$creditcard];

            return $this->gateway->commit('post', $this->gateway->buildUrlFromString('customers'), $params);
        }
    }

    /**
     * Delete a credit card.
     *
     * @param string   $id
     * @param string[] $options
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function delete($id, $options = [])
    {
        if (isset($options['card_id'])) {
            return $this->gateway->commit('delete', $this->gateway->buildUrlFromString('customers/'.$id.'/cards/'.$options['card_id']));
        } else {
            return $this->gateway->commit('delete', $this->gateway->buildUrlFromString('customers/'.$id));
        }
    }
 }
