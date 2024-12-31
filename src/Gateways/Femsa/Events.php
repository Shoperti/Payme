<?php

namespace Shoperti\PayMe\Gateways\Femsa;

use Shoperti\PayMe\Contracts\EventInterface;
use Shoperti\PayMe\Gateways\AbstractApi;

/**
 * This is the Conekta events class.
 *
 * @author Joseph Cohen <joseph.cohen@dinkbit.com>
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
        return $this->gateway->commit('get', $this->gateway->buildUrlFromString('events'));
    }

    /**
     * Find an event by its id.
     *
     * @param int|string $id
     * @param array      $options
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function find($id, array $options = [])
    {
        return $this->gateway->commit('get', $this->gateway->buildUrlFromString('events'), ['id' => $id]);
    }
}
