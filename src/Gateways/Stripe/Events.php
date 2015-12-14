<?php

namespace Shoperti\PayMe\Gateways\Stripe;

use InvalidArgumentException;
use Shoperti\PayMe\Contracts\EventInterface;
use Shoperti\PayMe\Gateways\AbstractApi;

/**
  * This is the conekta events class.
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
        return $this->commit('get', $this->buildUrlFromString('events'));
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
            throw new InvalidArgumentException('We need an id');
        }

        return $this->commit('get', $this->buildUrlFromString("events/{$id}"));
    }
 }
