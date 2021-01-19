<?php

namespace Shoperti\PayMe\Gateways\Stripe;

use Shoperti\PayMe\Contracts\EventInterface;
use Shoperti\PayMe\Gateways\AbstractApi;

/**
 * This is the Stripe events class.
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
        return $this->gateway->commit('get', $this->gateway->buildUrlFromString('events'));
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
        return $this->gateway->commit('get', $this->gateway->buildUrlFromString("events/{$id}"));
    }
}
