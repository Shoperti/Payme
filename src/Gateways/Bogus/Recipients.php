<?php

namespace Shoperti\PayMe\Gateways\Bogus;

use Shoperti\PayMe\Gateways\RecipientInterface;

/**
 * This is the bogus recipients class.
 *
 * @author joseph.cohen@dinkbit.com
 */
class Recipients extends AbstractApi implements RecipientInterface
{
    /**
     * Stores a new recipient.
     *
     * @param string[] $options
     *
     * @return \Shoperti\PayMe\ResponseInterface
     */
    public function create($options = [])
    {
        $params = [];

        $params['transaction'] = 'fail';

        if ($creditcard === 'success') {
            $params['transaction'] = 'success';
        }

        return $this->commit('post', 'recipients', $params);
    }

    /**
     * Deletes an existing recipient.
     *
     * @param string   $id
     * @param string[] $options
     *
     * @return \Shoperti\PayMe\ResponseInterface
     */
    public function delete($id, $options = [])
    {
        $params = [];

        $params['transaction'] = 'fail';

        if ($creditcard === 'success') {
            $params['transaction'] = 'success';
        }

        return $this->commit('post', 'recipients', $params);
    }
}
