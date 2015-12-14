<?php

namespace Shoperti\PayMe\Gateways\Bogus;

use Shoperti\PayMe\Gateways\AbstractApi;
use Shoperti\PayMe\Contracts\CardInterface;

/**
 * This is the bogus cards class.
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
     * @return \Shoperti\PayMe\Transaction
     */
    public function create($creditcard, $options = [])
    {
        $params = [];

        $params['transaction'] = 'fail';

        if ($creditcard === 'success') {
            $params['transaction'] = 'success';
        }

        return $this->commit('post', 'create', $params);
    }

    /**
     * Deletes a credit card.
     *
     * @param string   $id
     * @param string[] $options
     *
     * @return \Shoperti\PayMe\Transaction
     */
    public function delete($id, $options = [])
    {
        $params = [];
        
        $params['transaction'] = 'fail';

        if ($creditcard === 'success') {
            $params['transaction'] = 'success';
        }

        return $this->commit('post', 'delete', $params);
    }
}
