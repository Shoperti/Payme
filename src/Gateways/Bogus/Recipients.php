<?php

namespace Shoperti\PayMe\Gateways\Bogus;

use Shoperti\PayMe\Contracts\RecipientInterface;
use Shoperti\PayMe\Gateways\AbstractApi;

/**
 * This is the bogus recipients class.
 *
 * @author Joseph Cohen <joseph.cohen@dinkbit.com>
 */
class Recipients extends AbstractApi implements RecipientInterface
{
    /**
     * Store a new recipient.
     *
     * @param string[] $attributes
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function create($attributes = [])
    {
        $params = [];

        $params['transaction'] = 'fail';

        if ($creditcard === 'success') {
            $params['transaction'] = 'success';
        }

        return $this->gateway->commit('post', 'recipients', $params);
    }

    /**
     * Delete an existing recipient.
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

        return $this->gateway->commit('post', 'recipients', $params);
    }
}
