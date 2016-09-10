<?php

namespace Shoperti\PayMe\Gateways\OpenPay;

use BadMethodCallException;
use InvalidArgumentException;
use Shoperti\PayMe\Contracts\EventInterface;
use Shoperti\PayMe\Gateways\AbstractApi;

/**
 * This is the OpenPay events class.
 *
 * @author Arturo Rodríguez <arturo.rodriguez@dinkbit.com>
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
        if (!$id || func_num_args() < 2) {
            throw new InvalidArgumentException('We need an id as first parameter and the event as second parameter');
        }

        $event = func_get_args()[1];
        $endpoint = null;

        switch ($event) {
            case 'charge.created':
            case 'charge.succeeded':
            case 'charge.refunded':
            case 'charge.cancelled':
            case 'charge.failed':
            case 'charge.rescored.to.decline':
                $endpoint = 'charges'; break;

            case 'chargeback.created':
            case 'chargeback.accepted':
            case 'chargeback.rejected':
                $endpoint = 'charges'; break;

            case 'fee.refund.succeeded':
            case 'fee.succeeded':
                $endpoint = 'charges'; break;

            case 'order.activated':
            case 'order.cancelled':
            case 'order.completed':
            case 'order.created':
            case 'order.expired':
            case 'order.payment.cancelled':
            case 'order.payment.received':
                $endpoint = 'charges'; break;

            case 'payout.created':
            case 'payout.failed':
            case 'payout.succeeded':
                $endpoint = 'charges'; break;

            case 'spei.received':
                $endpoint = 'charges'; break;

            case 'subscription.charge.failed':
                $endpoint = 'charges'; break;

            case 'transfer.succeeded':
                $endpoint = 'charges'; break;
        }

        if (!$endpoint) {
            throw new InvalidArgumentException(sprintf("The specified event '%s' is not valid", $event));
        }

        /* @var \Shoperti\PayMe\Response $response */
        $response = $this->gateway->commit('get', $this->gateway->buildUrlFromString("{$endpoint}/{$id}"));
        $response->type = $event;

        return $response;
    }
}
