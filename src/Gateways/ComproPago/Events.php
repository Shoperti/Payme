<?php

namespace Shoperti\PayMe\Gateways\ComproPago;

use BadMethodCallException;
use InvalidArgumentException;
use Shoperti\PayMe\Contracts\EventInterface;
use Shoperti\PayMe\Gateways\AbstractApi;

/**
 * This is the Compro Pago events class.
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
        throw new BadMethodCallException();
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

        return $this->gateway->commit('get', $this->gateway->buildUrlFromString('charges').'/'.$id);
    }
}
