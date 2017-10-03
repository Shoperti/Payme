<?php

namespace Shoperti\PayMe\Gateways\MercadoPagoBasic;

use BadMethodCallException;
use Shoperti\PayMe\Contracts\EventInterface;
use Shoperti\PayMe\Gateways\AbstractApi;
use Shoperti\PayMe\Support\Arr;

/**
 * This is the MercadoPagoBasic events class.
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
     * @param array      $options
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function find($id, array $options = [])
    {
        $topic = Arr::get($options, 'topic');

        if ($topic == 'payment') {
            $response = $this->gateway->commit('get', $this->gateway->buildUrlFromString('collections/notifications').'/'.$id);

            if (!$response->success()) {
                return $response;
            }

            $responseData = $response->data();

            $id = Arr::get($responseData, 'merchant_order_id');
        }

        return $this->gateway->commit('get', $this->gateway->buildUrlFromString('merchant_orders').'/'.$id);
    }
}
