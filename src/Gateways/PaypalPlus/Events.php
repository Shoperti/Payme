<?php

namespace Shoperti\PayMe\Gateways\PaypalPlus;

use Shoperti\PayMe\Gateways\PaypalExpress\Events as PaypalExpressEvents;
use Shoperti\PayMe\Support\Arr;

/**
 * This is the PayPal Plus events class.
 *
 * @author Arturo Rodríguez <arturo.rodriguez@dinkbit.com>
 */
class Events extends PaypalExpressEvents
{
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
        $event = Arr::get($options, 'resource_type');

        if ($event === 'sale') {
            return $this->gateway->commit(
                'get',
                $this->gateway->buildUrlFromString(sprintf('payments/payment/%s', $id)),
                ['token' => Arr::get($options, 'token')]
            );
        }

        return parent::find($id, $options);
    }
}
