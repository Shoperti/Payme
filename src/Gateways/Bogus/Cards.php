<?php

namespace Shoperti\PayMe\Gateways\Bogus;

use Shoperti\PayMe\Contracts\CardInterface;
use Shoperti\PayMe\Gateways\AbstractApi;

/**
 * This is the bogus cards class.
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
        $params = [];

        $params['transaction'] = 'fail';

        if ($creditcard === 'success') {
            $params['transaction'] = 'success';
        }

        return $this->gateway->commit('post', 'create', $params);
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
        $params = [];

        $params['transaction'] = 'fail';

        if ($creditcard === 'success') {
            $params['transaction'] = 'success';
        }

        return $this->gateway->commit('post', 'delete', $params);
    }
}
