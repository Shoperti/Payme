<?php

namespace Shoperti\PayMe\Gateways\Conekta;

use Shoperti\PayMe\Contracts\CardInterface;
use Shoperti\PayMe\Gateways\AbstractApi;

/**
  * This is the conekta cards class.
  *
  * @author joseph.cohen@dinkbit.com
  */
 class Cards extends AbstractApi implements CardInterface
 {
     /**
     * Stores a credit card.
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

            return $this->commit('post', $this->buildUrlFromString('customers/'.$options['customer'].'/cards'), $params);
        } else {
            $params['email'] = Arr::get($options, 'email');
            $params['name'] = Arr::get($options, 'name');
            $params['cards'] = [$creditcard];

            return $this->commit('post', $this->buildUrlFromString('customers'), $params);
        }
    }

    /**
     * Deletes a credit card.
     *
     * @param string   $id
     * @param string[] $options
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function delete($id, $options = [])
    {
        if (isset($options['card_id'])) {
            return $this->commit('delete', $this->buildUrlFromString('customers/'.$id.'/cards/'.$options['card_id']));
        } else {
            return $this->commit('delete', $this->buildUrlFromString('customers/'.$id));
        }
    }
 }
