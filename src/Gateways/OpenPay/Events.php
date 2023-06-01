<?php

namespace Shoperti\PayMe\Gateways\OpenPay;

use BadMethodCallException;
use InvalidArgumentException;
use Shoperti\PayMe\Contracts\EventInterface;
use Shoperti\PayMe\Gateways\AbstractApi;
use Shoperti\PayMe\Support\Arr;

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
     * @param array      $options
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function find($id, array $options = [])
    {
        $event = Arr::get($options, 'event');

        if (!$event) {
            throw new InvalidArgumentException("You must specify the 'event' key in the options array");
        }

        $endpoint = null;

        switch ($event) {
            case 'charge.created':
            case 'charge.succeeded':
            case 'charge.refunded':
            case 'charge.cancelled':
            case 'charge.failed':
            case 'charge.rescored.to.decline':
                $endpoint = 'charges';
                break;

            case 'chargeback.created':
            case 'chargeback.accepted':
            case 'chargeback.rejected':
                $endpoint = 'charges';
                break;

            case 'fee.refund.succeeded':
            case 'fee.succeeded':
                $endpoint = 'charges';
                break;

            case 'order.activated':
            case 'order.cancelled':
            case 'order.completed':
            case 'order.created':
            case 'order.expired':
            case 'order.payment.cancelled':
            case 'order.payment.received':
                $endpoint = 'charges';
                break;

            case 'payout.created':
            case 'payout.failed':
            case 'payout.succeeded':
                $endpoint = 'charges';
                break;

            case 'spei.received':
                $endpoint = 'charges';
                break;

            case 'subscription.charge.failed':
                $endpoint = 'charges';
                break;

            case 'transfer.succeeded':
                $endpoint = 'charges';
                break;
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
