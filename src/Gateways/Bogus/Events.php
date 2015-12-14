<?php

namespace Shoperti\PayMe\Gateways\Bogus;

use InvalidArgumentException;
use Shoperti\PayMe\Contracts\EventInterface;
use Shoperti\PayMe\Gateways\AbstractApi;

/**
 * This is the conekta charges class.
 *
 * @author joseph.cohen@dinkbit.com
 */
class Events extends AbstractApi implements EventInterface
{
    /**
     * Find all events.
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function all()
    {
        $params = [];

        $params['transaction'] = 'fail';

        if ($creditcard === 'success') {
            $params['transaction'] = 'success';
        }

        return $this->commit('get', 'events', $params);
    }

    /**
     * Find an event by its id.
     *
     * @param int|string $id
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function find($id = null)
    {
        if (!$id) {
            throw new InvalidArgumentException('Please add an id.');
        }

        $params = [];

        $params['transaction'] = 'fail';

        if ($creditcard === 'success') {
            $params['transaction'] = 'success';
        }

        return $this->commit('get', "events/{$id}", $params);
    }
}
