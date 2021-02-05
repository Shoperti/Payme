<?php

namespace Shoperti\PayMe\Gateways\Bogus;

use Shoperti\PayMe\Contracts\EventInterface;
use Shoperti\PayMe\Gateways\AbstractApi;

/**
 * This is the bogus events class.
 *
 * @author Joseph Cohen <joseph.cohen@dinkbit.com>
 */
class Events extends AbstractApi implements EventInterface
{
    /**
     * Find all events.
     *
     * @param string[] $headers
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function all($headers = [])
    {
        $params = [];

        $params['transaction'] = 'success';

        return $this->gateway->commit('get', 'events', $params);
    }

    /**
     * Find an event by its id.
     *
     * @param int|string $id
     * @param array      $options
     * @param string[]   $headers
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function find($id, array $options = [], $headers = [])
    {
        $params = [];

        $params['transaction'] = 'success';

        return $this->gateway->commit('get', "events/{$id}", $params);
    }
}
